<?php

namespace Ninja\Larasoul\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\RiskStatus;
use Ninja\Larasoul\Enums\VerificationType;
use Ninja\Larasoul\Enums\VerisoulDecision;

/**
 * Ninja\Larasoul\Models\RiskProfile
 *
 * @mixin Builder
 *
 * @property int $id
 * @property int $user_id
 * @property RiskStatus $status
 * @property VerisoulDecision|null $decision
 * @property float|null $score
 * @property array|null $signals
 * @property Carbon|null $phone_verified_at
 * @property Carbon|null $face_verified_at
 * @property Carbon|null $identity_verified_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $expires_at
 * @property string|null $failure_reason
 * @property Carbon|null $last_risk_check_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Authenticatable $user
 * @property-read string $risk_level
 * @property-read array $verified_types
 * @property-read int $verification_score
 * @property-read string $health_status
 * @property-read int|null $days_until_expiration
 * @property-read bool $is_expired
 * @property-read bool $is_about_to_expire
 * @property-read bool $is_verified
 * @property-read bool $is_fully_verified
 * @property-read bool $has_face_verification
 * @property-read bool $has_phone_verification
 * @property-read bool $has_identity_verification
 * @property-read bool $requires_manual_review
 *
 * @method static Builder|RiskProfile verified()
 * @method static Builder|RiskProfile failed()
 * @method static Builder|RiskProfile pending()
 * @method static Builder|RiskProfile manualReview()
 * @method static Builder|RiskProfile expired()
 * @method static Builder|RiskProfile aboutToExpire(int $warningDays = 7)
 * @method static Builder|RiskProfile lowRisk()
 * @method static Builder|RiskProfile mediumRisk()
 * @method static Builder|RiskProfile highRisk()
 * @method static Builder|RiskProfile identityVerified()
 * @method static Builder|RiskProfile faceVerified()
 * @method static Builder|RiskProfile phoneVerified()
 * @method static Builder|RiskProfile recent(int $days = 30)
 * @method static Builder|RiskProfile needsRiskCheck(int $intervalDays = 30)
 * @method static Builder|RiskProfile newModelQuery()
 * @method static Builder|RiskProfile newQuery()
 * @method static Builder|RiskProfile query()
 * @method static Builder|RiskProfile whereId($value)
 * @method static Builder|RiskProfile whereUserId($value)
 * @method static Builder|RiskProfile whereStatus($value)
 * @method static Builder|RiskProfile whereDecision($value)
 * @method static Builder|RiskProfile whereScore($value)
 * @method static Builder|RiskProfile whereSignals($value)
 * @method static Builder|RiskProfile whereFaceVerifiedAt($value)
 * @method static Builder|RiskProfile wherePhoneVerifiedAt($value)
 * @method static Builder|RiskProfile whereIdentityVerifiedAt($value)
 * @method static Builder|RiskProfile whereVerifiedAt($value)
 * @method static Builder|RiskProfile whereExpiresAt($value)
 * @method static Builder|RiskProfile whereLastRiskCheckAt($value)
 * @method static Builder|RiskProfile whereCreatedAt($value)
 * @method static Builder|RiskProfile whereUpdatedAt($value)
 */ class RiskProfile extends Model
{
    use HasFactory;

    protected $table = 'risk_profile';

    protected $fillable = [
        'user_id',
        'status',
        'decision',
        'score',
        'signals',
        'face_verified_at',
        'phone_verified_at',
        'identity_verified_at',
        'verified_at',
        'expires_at',
        'last_risk_check_at',
    ];

    protected $casts = [
        'decision' => VerisoulDecision::class,
        'score' => 'decimal:2',
        'signals' => 'array',
        'face_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'identity_verified_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_risk_check_at' => 'datetime',
    ];

    public static function for(RiskProfilable $user): RiskProfile
    {
        if (! $user->hasRiskProfile()) {
            $riskProfile = RiskProfile::create([
                'user_id' => $user->getAuthIdentifier(),
                'status' => RiskStatus::Pending,
            ]);
        } else {
            $riskProfile = $user->getRiskProfile();
        }

        return $riskProfile;
    }

    /**
     * Get the user that owns this verification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Check if verification is complete
     */
    public function isVerified(): bool
    {
        return $this->verification_status === RiskStatus::Verified &&
            $this->decision === VerisoulDecision::Real &&
            ! $this->isExpired();
    }

    /**
     * Check if fully verified (all verification types)
     */
    public function isFullyVerified(): bool
    {
        return $this->isVerified() &&
            $this->hasPhoneVerification() &&
            $this->hasIdentityVerification() &&
            $this->hasFaceVerification();
    }

    /**
     * Check if has face verification
     */
    public function hasFaceVerification(): bool
    {
        return ! is_null($this->face_verified_at);
    }

    /**
     * Check if has phone verification
     */
    public function hasPhoneVerification(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Check if has identity verification
     */
    public function hasIdentityVerification(): bool
    {
        return ! is_null($this->identity_verified_at);
    }

    /**
     * Get risk level based on score
     */
    public function getRiskLevel(): RiskLevel
    {
        if ($this->risk_score === null) {
            return RiskLevel::Unknown;
        }

        return match (true) {
            $this->risk_score <= 0.3 => RiskLevel::Low,
            $this->risk_score <= 0.7 => RiskLevel::Medium,
            $this->risk_score <= 0.9 => RiskLevel::High,
            default => RiskLevel::Critical,
        };
    }

    /**
     * Check if requires manual review
     */
    public function requiresManualReview(): bool
    {
        return $this->verification_status === RiskStatus::ManualReview ||
            $this->decision === VerisoulDecision::Suspicious ||
            ($this->risk_score >= 0.4 && $this->risk_score < 0.8);
    }

    /**
     * Get verified types
     *
     * @return array<VerificationType>
     */
    public function getVerifiedTypes(): array
    {
        $types = [];

        if ($this->hasFaceVerification()) {
            $types[] = VerificationType::Face;
        }

        if ($this->hasPhoneVerification()) {
            $types[] = VerificationType::Phone;
        }

        if ($this->hasIdentityVerification()) {
            $types[] = VerificationType::Identity;
        }

        return $types;
    }

    /**
     * Check if verification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function needsVerification(): bool
    {
        return $this->isExpired() || in_array($this->verification_status, [RiskStatus::Expired, RiskStatus::Pending], true);
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at));
    }

    /**
     * Check if verification is about to expire
     */
    public function isAboutToExpire(int $warningDays = 7): bool
    {
        $daysUntilExpiration = $this->getDaysUntilExpiration();

        return $daysUntilExpiration !== null && $daysUntilExpiration <= $warningDays;
    }

    /**
     * Mark verification as successful
     */
    public function markAsVerified(): self
    {
        $this->update([
            'verification_status' => RiskStatus::Verified,
            'verified_at' => now(),
            'expires_at' => now()->addMonths(config('larasoul.verification.expiry_months', 12)),
        ]);

        return $this;
    }

    /**
     * Mark verification as failed
     */
    public function markAsFailed(?string $reason = null): self
    {
        $this->update([
            'verification_status' => RiskStatus::Failed,
        ]);

        return $this;
    }

    /**
     * Mark for manual review
     */
    public function markForManualReview(?string $reason = null): self
    {
        $this->update([
            'verification_status' => RiskStatus::ManualReview,
        ]);

        return $this;
    }

    /**
     * Update risk check timestamp
     */
    public function updateRiskCheck(): self
    {
        $this->update(['last_risk_check_at' => now()]);

        return $this;
    }

    /**
     * Scope: Filter by verification status
     */
    public function scopeWithStatus($query, RiskStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only verified records
     */
    public function scopeVerified($query)
    {
        return $query->withStatus(RiskStatus::Verified);
    }

    /**
     * Scope: Only failed records
     */
    public function scopeFailed($query)
    {
        return $query->withStatus(RiskStatus::Failed);
    }

    /**
     * Scope: Only pending records
     */
    public function scopePending($query)
    {
        return $query->withStatus(RiskStatus::Pending);
    }

    /**
     * Scope: Only manual review records
     */
    public function scopeManualReview($query)
    {
        return $query->withStatus(RiskStatus::ManualReview);
    }

    /**
     * Scope: Only expired records
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: About to expire
     */
    public function scopeAboutToExpire($query, int $warningDays = 7)
    {
        return $query->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($warningDays));
    }

    /**
     * Scope: Low risk
     */
    public function scopeLowRisk($query)
    {
        return $query->where('risk_score', '<=', 0.3);
    }

    /**
     * Scope: Medium risk
     */
    public function scopeMediumRisk($query)
    {
        return $query->where('risk_score', '>', 0.3)
            ->where('risk_score', '<=', 0.7);
    }

    /**
     * Scope: High risk
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>', 0.7);
    }

    /**
     * Scope: High risk
     */
    public function scopeCriticalRisk($query)
    {
        return $query->where('risk_score', '>', 0.9);
    }

    /**
     * Scope: With face verification
     */
    public function scopeFaceVerified($query)
    {
        return $query->whereNotNull('face_verified_at');
    }

    /**
     * Scope: With phone verification
     */
    public function scopePhoneVerified($query)
    {
        return $query->whereNotNull('phone_verified_at');
    }

    /**
     * Scope: Recent verifications
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Needs risk check
     */
    public function scopeNeedsRiskCheck($query, int $intervalDays = 30)
    {
        return $query->where(function ($q) use ($intervalDays) {
            $q->whereNull('last_risk_check_at')
                ->orWhere('last_risk_check_at', '<=', now()->subDays($intervalDays));
        });
    }

    /**
     * Check if risk check is due
     */
    public function isRiskCheckDue(int $intervalDays = 30): bool
    {
        if (! $this->last_risk_check_at) {
            return true;
        }

        return $this->last_risk_check_at->diffInDays(now()) >= $intervalDays;
    }
}
