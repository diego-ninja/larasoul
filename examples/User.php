<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Traits\HasRiskProfile;
use Ninja\Larasoul\Traits\HasUserVerifications;

/**
 * Example User model showing proper Larasoul integration
 *
 * This model demonstrates how to:
 * - Implement RiskProfilable contract
 * - Use HasRiskProfile trait for 1:1 relationship with risk_profile table
 * - Use HasUserVerifications trait for 1:N relationship with user_verification table
 *
 * Relationships:
 * - User hasOne RiskProfile (1:1)
 * - User hasMany UserVerification (1:N)
 *
 * @property-read \Ninja\Larasoul\Models\RiskProfile|null $riskProfile
 * @property-read \Illuminate\Database\Eloquent\Collection|\Ninja\Larasoul\Models\UserVerification[] $userVerifications
 */
class User extends Authenticatable implements RiskProfilable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRiskProfile, HasUserVerifications;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Example usage methods showing how to use the traits
     */

    /**
     * Get user's overall verification status
     */
    public function getVerificationStatus(): string
    {
        if ($this->isFullyVerified()) {
            return 'fully_verified';
        }

        if ($this->hasFaceVerification() || $this->hasPhoneVerification() || $this->hasIdentityVerification()) {
            return 'partially_verified';
        }

        return 'unverified';
    }

    /**
     * Get user's risk assessment summary
     */
    public function getRiskSummary(): array
    {
        return [
            'is_assessed' => $this->isRiskAssessed(),
            'risk_level' => $this->getRiskLevel(),
            'risk_score' => $this->getRiskScore(),
            'decision' => $this->getDecision(),
            'is_expired' => $this->needsRiskAssessment(),
            'needs_assessment' => $this->isRiskAssessmentDue(),
            'signals' => $this->getRiskSignals(),
        ];
    }

    /**
     * Get user's verification summary
     */
    public function getVerificationSummary(): array
    {
        return [
            'completion_percentage' => $this->getVerificationCompletionPercentage(),
            'verified_types' => $this->getVerifiedTypes()->toArray(),
            'has_face' => $this->hasFaceVerification(),
            'has_phone' => $this->hasPhoneVerification(),
            'has_identity' => $this->hasIdentityVerification(),
            'is_fully_verified' => $this->isFullyVerified(),
            'pending_count' => $this->getPendingVerifications()->count(),
            'failed_count' => $this->getFailedVerifications()->count(),
            'expired_count' => $this->getExpiredVerifications()->count(),
            'expiring_soon_count' => $this->getExpiringSoonVerifications()->count(),
        ];
    }

    /**
     * Check if user needs any action
     */
    public function needsAction(): bool
    {
        return $this->isRiskAssessmentDue() ||
               $this->hasExpiredVerifications() ||
               $this->hasExpiringSoonVerifications();
    }

    /**
     * Get action items for user
     */
    public function getActionItems(): array
    {
        $actions = [];

        if ($this->isRiskAssessmentDue()) {
            $actions[] = [
                'type' => 'risk_assessment',
                'message' => 'Risk assessment is due',
                'priority' => 'high',
            ];
        }

        if ($this->hasExpiredVerifications()) {
            $actions[] = [
                'type' => 'expired_verifications',
                'message' => 'You have expired verifications',
                'priority' => 'high',
                'count' => $this->getExpiredVerifications()->count(),
            ];
        }

        if ($this->hasExpiringSoonVerifications()) {
            $actions[] = [
                'type' => 'expiring_verifications',
                'message' => 'You have verifications expiring soon',
                'priority' => 'medium',
                'count' => $this->getExpiringSoonVerifications()->count(),
            ];
        }

        if (! $this->isFullyVerified()) {
            $actions[] = [
                'type' => 'incomplete_verification',
                'message' => 'Complete your verification process',
                'priority' => 'low',
                'completion' => $this->getVerificationCompletionPercentage(),
            ];
        }

        return $actions;
    }

    /**
     * Example scopes usage
     */

    /**
     * Scope: Users needing attention
     */
    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('riskProfile', function ($riskQuery) {
                $riskQuery->whereNull('assessed_at')
                    ->orWhere('expires_at', '<=', now());
            })->orWhereHas('userVerifications', function ($verificationQuery) {
                $verificationQuery->where('expires_at', '<=', now()->addDays(7));
            });
        });
    }

    /**
     * Scope: High priority users (high risk or suspicious)
     */
    public function scopeHighPriority($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('risk_score', '>=', 0.7)
                ->orWhere('decision', 'suspicious');
        });
    }

    /**
     * Scope: Users with good standing
     */
    public function scopeGoodStanding($query)
    {
        return $query->whereHas('riskProfile', function ($q) {
            $q->where('decision', 'real')
                ->where('risk_score', '<=', 0.3);
        })->whereDoesntHave('userVerifications', function ($q) {
            $q->where('expires_at', '<=', now());
        });
    }
}
