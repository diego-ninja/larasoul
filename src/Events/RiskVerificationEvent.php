<?php

namespace Ninja\Larasoul\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ninja\Larasoul\Models\RiskProfile;

/**
 * Base class for verification events
 *
 * @property RiskProfile $verification
 */
abstract class RiskVerificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RiskProfile $riskProfile
    ) {}

    /**
     * Get the user associated with this verification
     */
    public function getUser(): Authenticatable
    {
        return $this->riskProfile->user;
    }

    /**
     * Get verification data for logging/notifications
     */
    public function getRiskData(): array
    {
        return [
            'user_id' => $this->riskProfile->user_id,
            'risk_profile_id' => $this->riskProfile->id,
            'status' => $this->riskProfile->status,
            'decision' => $this->riskProfile->decision?->value,
            'score' => $this->riskProfile->score,
            'level' => $this->riskProfile->getRiskLevel(),
            'verified_types' => $this->riskProfile->getVerifiedTypes(),
            'timestamp' => now()->toISOString(),
        ];
    }
}
