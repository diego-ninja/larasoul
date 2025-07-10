<?php

namespace Ninja\Larasoul\Events;

/**
 * Event fired when verification fails
 */
class UserRiskVerificationFailed extends RiskVerificationEvent
{
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
            'type' => 'verification_failed',
            'data' => array_merge($this->getRiskData(), [
                'success' => false,
                'failure_reason' => $this->riskProfile->failure_reason,
                'attempts' => $this->riskProfile->verification_attempts,
                'risk_flags' => $this->riskProfile->getRiskFlags(),
            ]),
        ];
    }
}
