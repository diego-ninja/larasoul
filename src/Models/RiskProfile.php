<?php

namespace Ninja\Larasoul\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\ValueObjects\RiskScore;

/**
 * Ninja\Larasoul\Models\RiskProfile
 *
 * @mixin Builder
 *
 * @property int $id
 * @property int $user_id
 * @property VerisoulDecision|null $decision
 * @property RiskLevel $risk_level
 * @property RiskScore|null $risk_score
 * @property RiskSignalCollection $risk_signals
 * @property Carbon|null $assessed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Authenticatable $user
 * @property-read int|null $days_until_expiration
 * @property-read bool $is_expired
 * @property-read bool $is_about_to_expire
 * @property-read bool $is_assessed
 * @property-read bool $is_low_risk
 * @property-read bool $is_high_risk
 * @property-read bool $is_real
 * @property-read bool $is_fake
 * @property-read bool $is_suspicious
 * @property-read bool $requires_manual_review
 * @property-read bool $needs_assessment
 *
 * @method static Builder|RiskProfile real()
 * @method static Builder|RiskProfile fake()
 * @method static Builder|RiskProfile suspicious()
 * @method static Builder|RiskProfile assessed()
 * @method static Builder|RiskProfile unassessed()
 * @method static Builder|RiskProfile expired()
 * @method static Builder|RiskProfile aboutToExpire(int $warningDays = 7)
 * @method static Builder|RiskProfile lowRisk()
 * @method static Builder|RiskProfile mediumRisk()
 * @method static Builder|RiskProfile highRisk()
 * @method static Builder|RiskProfile criticalRisk()
 * @method static Builder|RiskProfile withRiskLevel(RiskLevel $riskLevel)
 * @method static Builder|RiskProfile withDecision(VerisoulDecision $decision)
 * @method static Builder|RiskProfile recent(int $days = 30)
 * @method static Builder|RiskProfile needsAssessment(int $intervalDays = 30)
 * @method static Builder|RiskProfile newModelQuery()
 * @method static Builder|RiskProfile newQuery()
 * @method static Builder|RiskProfile query()
 * @method static Builder|RiskProfile whereId($value)
 * @method static Builder|RiskProfile whereUserId($value)
 * @method static Builder|RiskProfile whereDecision($value)
 * @method static Builder|RiskProfile whereRiskLevel($value)
 * @method static Builder|RiskProfile whereRiskScore($value)
 * @method static Builder|RiskProfile whereRiskSignals($value)
 * @method static Builder|RiskProfile whereAssessedAt($value)
 * @method static Builder|RiskProfile whereExpiresAt($value)
 * @method static Builder|RiskProfile whereCreatedAt($value)
 * @method static Builder|RiskProfile whereUpdatedAt($value)
 */ class RiskProfile extends Model
{
    use HasFactory;

    protected $table = 'risk_profile';

    protected $fillable = [
        'user_id',
        'decision',
        'risk_level',
        'risk_score',
        'risk_signals',
        'assessed_at',
        'expires_at',
    ];

    protected $casts = [
        'decision' => VerisoulDecision::class,
        'risk_level' => RiskLevel::class,
        'risk_score' => RiskScore::class,
        'risk_signals' => \Ninja\Larasoul\Casts\RiskSignalCollectionCast::class,
        'assessed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function for(RiskProfilable $user): RiskProfile
    {
        if (! $user->hasRiskProfile()) {
            $riskProfile = RiskProfile::create([
                'user_id' => $user->getAuthIdentifier(),
                'risk_level' => RiskLevel::Unknown,
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
     * Check if risk profile is assessed
     */
    public function isAssessed(): bool
    {
        return $this->decision !== null && $this->assessed_at !== null;
    }

    /**
     * Check if risk profile is low risk
     */
    public function isLowRisk(): bool
    {
        return $this->risk_level === RiskLevel::Low;
    }

    /**
     * Check if risk profile is high risk
     */
    public function isHighRisk(): bool
    {
        return $this->risk_level === RiskLevel::High || $this->risk_level === RiskLevel::Critical;
    }

    /**
     * Check if decision is real
     */
    public function isReal(): bool
    {
        return $this->decision === VerisoulDecision::Real;
    }

    /**
     * Check if decision is fake
     */
    public function isFake(): bool
    {
        return $this->decision === VerisoulDecision::Fake;
    }

    /**
     * Check if decision is suspicious
     */
    public function isSuspicious(): bool
    {
        return $this->decision === VerisoulDecision::Suspicious;
    }

    /**
     * Get risk level (from database or calculated from score)
     */
    public function getRiskLevel(): RiskLevel
    {
        if ($this->risk_level) {
            return $this->risk_level;
        }

        if ($this->risk_score === null) {
            return RiskLevel::Unknown;
        }

        return $this->risk_score->level();
    }

    /**
     * Check if requires manual review
     */
    public function requiresManualReview(): bool
    {
        return $this->decision === VerisoulDecision::Suspicious ||
            ($this->risk_score?->isBetween(0.4, 0.8) ?? false);
    }

    /**
     * Update risk assessment
     */
    public function updateRiskAssessment(
        VerisoulDecision $decision,
        RiskLevel $riskLevel,
        RiskScore $riskScore,
        ?array $riskSignals = null
    ): self {
        $this->update([
            'decision' => $decision,
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'risk_signals' => $riskSignals,
            'assessed_at' => now(),
            'expires_at' => now()->addDays(config('larasoul.verification.expirations.risk_assessment', 30)),
        ]);

        return $this;
    }

    /**
     * Check if verification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if needs assessment
     */
    public function needsAssessment(): bool
    {
        return $this->isExpired() || ! $this->isAssessed();
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
     * Mark as low risk
     */
    public function markAsLowRisk(): self
    {
        $this->update([
            'decision' => VerisoulDecision::Real,
            'risk_level' => RiskLevel::Low,
            'assessed_at' => now(),
            'expires_at' => now()->addDays(config('larasoul.verification.expirations.risk_assessment', 30)),
        ]);

        return $this;
    }

    /**
     * Mark as high risk
     */
    public function markAsHighRisk(): self
    {
        $this->update([
            'decision' => VerisoulDecision::Fake,
            'risk_level' => RiskLevel::High,
            'assessed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as suspicious
     */
    public function markAsSuspicious(): self
    {
        $this->update([
            'decision' => VerisoulDecision::Suspicious,
            'risk_level' => RiskLevel::Medium,
            'assessed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Scope: Filter by decision
     */
    public function scopeWithDecision($query, VerisoulDecision $decision)
    {
        return $query->where('decision', $decision);
    }

    /**
     * Scope: Only real profiles
     */
    public function scopeReal($query)
    {
        return $query->withDecision(VerisoulDecision::Real);
    }

    /**
     * Scope: Only fake profiles
     */
    public function scopeFake($query)
    {
        return $query->withDecision(VerisoulDecision::Fake);
    }

    /**
     * Scope: Only suspicious profiles
     */
    public function scopeSuspicious($query)
    {
        return $query->withDecision(VerisoulDecision::Suspicious);
    }

    /**
     * Scope: Only assessed profiles
     */
    public function scopeAssessed($query)
    {
        return $query->whereNotNull('assessed_at');
    }

    /**
     * Scope: Only unassessed profiles
     */
    public function scopeUnassessed($query)
    {
        return $query->whereNull('assessed_at');
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
        return $query->where('risk_score', '<=', config('larasoul.verification.risk_thresholds.low'));
    }

    /**
     * Scope: Medium risk
     */
    public function scopeMediumRisk($query)
    {
        return $query->where('risk_score', '>', config('larasoul.verification.risk_thresholds.low'))
            ->where('risk_score', '<=', config('larasoul.verification.risk_thresholds.medium'));
    }

    /**
     * Scope: High risk
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>', config('larasoul.verification.risk_thresholds.medium'));
    }

    /**
     * Scope: High risk
     */
    public function scopeCriticalRisk($query)
    {
        return $query->where('risk_score', '>', config('larasoul.verification.risk_thresholds.high'));
    }

    /**
     * Scope: Filter by risk level
     */
    public function scopeWithRiskLevel($query, RiskLevel $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    /**
     * Scope: Recent verifications
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Needs assessment
     */
    public function scopeNeedsAssessment($query, int $intervalDays = 30)
    {
        return $query->where(function ($q) {
            $q->whereNull('assessed_at')
                ->orWhere('expires_at', '<=', now());
        });
    }
}
