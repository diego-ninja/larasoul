<?php

namespace Ninja\Larasoul\Events;

use Ninja\Larasoul\Enums\RiskStatus;
use Ninja\Larasoul\Models\RiskProfile;

/**
 * Event fired when verification status changes
 */
class UserRiskVerificationStatusChanged extends RiskVerificationEvent
{
    public function __construct(
        public RiskProfile $riskProfile,
        public RiskStatus|string $oldStatus,
        public RiskStatus|string $newStatus
    ) {
        parent::__construct($riskProfile);
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            "user.{$this->riskProfile->user_id}.verification",
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'verification_status_changed',
            'data' => array_merge($this->getRiskData(), [
                'old_status' => is_string($this->oldStatus) ? $this->oldStatus : $this->oldStatus->value,
                'new_status' => is_string($this->newStatus) ? $this->newStatus : $this->newStatus->value,
                'changed_at' => now()->toISOString(),
            ]),
        ];
    }
}
