<?php

namespace Ninja\Larasoul\Events;

final class UserRiskVerificationStarted extends RiskVerificationEvent
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
            'type' => 'risk_verification_started',
            'data' => $this->getRiskData(),
        ];
    }
}
