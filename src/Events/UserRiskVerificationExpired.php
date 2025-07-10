<?php

namespace Ninja\Larasoul\Events;

/**
 * Event fired when verification expires
 */
class UserRiskVerificationExpired extends RiskVerificationEvent
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
            'type' => 'verification_expired',
            'data' => array_merge($this->getRiskData(), [
                'expired_at' => $this->riskProfile->expires_at,
                'days_expired' => $this->riskProfile->expires_at ?
                    now()->diffInDays($this->riskProfile->expires_at) : null,
            ]),
        ];
    }
}
