<?php

namespace Ninja\Larasoul\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ninja\Larasoul\Api\Responses\VerifyFaceResponse;
use Ninja\Larasoul\Api\Responses\VerifyIdResponse;
use Ninja\Larasoul\Casts\RiskSignalCollectionCast;
use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\Enums\VerificationStatus;
use Ninja\Larasoul\Enums\VerificationType;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\ValueObjects\RiskScore;

/**
 * Ninja\Larasoul\Models\UserVerification
 *
 * @mixin Builder
 *
 * @property int $id
 * @property int $user_id
 * @property VerificationType $type
 * @property VerificationStatus $status
 * @property VerisoulDecision|null $decision
 * @property RiskScore|null $risk_score
 * @property RiskSignalCollection $risk_signals
 * @property array|null $risk_flags
 * @property Carbon|null $verified_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Authenticatable $user
 * @property-read bool $is_verified
 * @property-read bool $is_expired
 * @property-read bool $is_about_to_expire
 * @property-read bool $is_pending
 * @property-read bool $is_failed
 * @property-read int|null $days_until_expiration
 *
 * @method static Builder|UserVerification pending()
 * @method static Builder|UserVerification verified()
 * @method static Builder|UserVerification failed()
 * @method static Builder|UserVerification expired()
 * @method static Builder|UserVerification aboutToExpire(int $warningDays = 7)
 * @method static Builder|UserVerification ofType(VerificationType $type)
 * @method static Builder|UserVerification face()
 * @method static Builder|UserVerification phone()
 * @method static Builder|UserVerification identity()
 * @method static Builder|UserVerification real()
 * @method static Builder|UserVerification fake()
 * @method static Builder|UserVerification suspicious()
 * @method static Builder|UserVerification recent(int $days = 30)
 * @method static Builder|UserVerification newModelQuery()
 * @method static Builder|UserVerification newQuery()
 * @method static Builder|UserVerification query()
 * @method static Builder|UserVerification whereId($value)
 * @method static Builder|UserVerification whereUserId($value)
 * @method static Builder|UserVerification whereType($value)
 * @method static Builder|UserVerification whereStatus($value)
 * @method static Builder|UserVerification whereDecision($value)
 * @method static Builder|UserVerification whereRiskScore($value)
 * @method static Builder|UserVerification whereRiskSignals($value)
 * @method static Builder|UserVerification whereRiskFlags($value)
 * @method static Builder|UserVerification whereVerifiedAt($value)
 * @method static Builder|UserVerification whereExpiresAt($value)
 * @method static Builder|UserVerification whereCreatedAt($value)
 * @method static Builder|UserVerification whereUpdatedAt($value)
 */
class UserVerification extends Model
{
    use HasFactory;

    protected $table = 'user_verification';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'decision',
        'risk_score',
        'risk_signals',
        'risk_flags',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'type' => VerificationType::class,
        'status' => VerificationStatus::class,
        'decision' => VerisoulDecision::class,
        'risk_score' => RiskScore::class,
        'risk_signals' => RiskSignalCollectionCast::class,
        'risk_flags' => 'array',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns this verification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public static function forUser(Authenticatable $user, VerificationType $type): self
    {
        return new self([
            'user_id' => $user->getAuthIdentifier(),
            'type' => $type, // Default type, can be changed later
            'status' => VerificationStatus::Pending,
        ]);
    }

    public static function fromApiResponse(VerifyIdResponse|VerifyFaceResponse $response, Authenticatable $user): self
    {
        $verification = new self;
        $verification->user_id = $user->getAuthIdentifier();
        $verification->type = $response->type;
        $verification->status = $response->isSuccessful() ? VerificationStatus::Verified : VerificationStatus::Failed;
        $verification->decision = $response->decision;
        $verification->risk_score = $response->riskScore;
        $verification->risk_signals = $response->riskSignals->toArray();
        $verification->risk_flags = $response->riskFlags->toArray();

        if ($response->isSuccessful()) {
            $verification->verified_at = $response->verifiedAt;
            $verification->expires_at = $response->expiresAt;
        }

        return $verification;
    }

    /**
     * Check if verification is verified
     */
    public function isVerified(): bool
    {
        return $this->status === VerificationStatus::Verified &&
            $this->decision === VerisoulDecision::Real &&
            ! $this->isExpired();
    }

    /**
     * Check if verification is pending
     */
    public function isPending(): bool
    {
        return $this->status === VerificationStatus::Pending;
    }

    /**
     * Check if verification is failed
     */
    public function isFailed(): bool
    {
        return $this->status === VerificationStatus::Failed;
    }

    /**
     * Check if verification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
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
     * Mark verification as successful
     */
    public function markAsVerified(): self
    {
        $this->update([
            'status' => VerificationStatus::Verified,
            'decision' => VerisoulDecision::Real,
            'verified_at' => now(),
            'expires_at' => now()->addDays($this->type->getExpirationPeriod()),
        ]);

        return $this;
    }

    /**
     * Mark verification as failed
     */
    public function markAsFailed(): self
    {
        $this->update([
            'status' => VerificationStatus::Failed,
            'decision' => VerisoulDecision::Fake,
        ]);

        return $this;
    }

    /**
     * Mark verification as suspicious
     */
    public function markAsSuspicious(): self
    {
        $this->update([
            'status' => VerificationStatus::ManualReview,
            'decision' => VerisoulDecision::Suspicious,
        ]);

        return $this;
    }

    /**
     * Update risk data
     */
    public function updateRiskData(
        RiskScore $riskScore,
        ?array $riskSignals = null,
        ?array $riskFlags = null,
        ?VerisoulDecision $decision = null
    ): self {
        $this->update([
            'risk_score' => $riskScore,
            'risk_signals' => $riskSignals,
            'risk_flags' => $riskFlags,
            'decision' => $decision,
        ]);

        return $this;
    }

    /**
     * Scope: Filter by verification type
     */
    public function scopeOfType(Builder $query, VerificationType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Only face verifications
     */
    public function scopeFace(Builder $query): Builder
    {
        return $query->ofType(VerificationType::Face);
    }

    /**
     * Scope: Only phone verifications
     */
    public function scopePhone(Builder $query): Builder
    {
        return $query->ofType(VerificationType::Phone);
    }

    /**
     * Scope: Only identity verifications
     */
    public function scopeIdentity(Builder $query): Builder
    {
        return $query->ofType(VerificationType::Identity);
    }

    /**
     * Scope: Only pending verifications
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', VerificationStatus::Pending);
    }

    /**
     * Scope: Only verified verifications
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', VerificationStatus::Verified);
    }

    /**
     * Scope: Only failed verifications
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', VerificationStatus::Failed);
    }

    /**
     * Scope: Only expired verifications
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: About to expire verifications
     */
    public function scopeAboutToExpire(Builder $query, int $warningDays = 7): Builder
    {
        return $query->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($warningDays));
    }

    /**
     * Scope: Only real verifications
     */
    public function scopeReal(Builder $query): Builder
    {
        return $query->where('decision', VerisoulDecision::Real);
    }

    /**
     * Scope: Only fake verifications
     */
    public function scopeFake(Builder $query): Builder
    {
        return $query->where('decision', VerisoulDecision::Fake);
    }

    /**
     * Scope: Only suspicious verifications
     */
    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('decision', VerisoulDecision::Suspicious);
    }

    /**
     * Scope: Recent verifications
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
