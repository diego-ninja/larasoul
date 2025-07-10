<?php

namespace Ninja\Larasoul\Events;

use Ninja\Larasoul\Models\RiskProfile;

/**
 * Event fired when face verification fails
 */
class FaceRiskVerificationFailed extends RiskVerificationEvent
{
    public function __construct(
        public RiskProfile $riskProfile,
        public string $reason
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
            'type' => 'face_verification_failed',
            'data' => array_merge($this->getRiskData(), [
                'failure_reason' => $this->reason,
                'face_match_score' => $this->riskProfile->face_match_score,
                'can_retry' => $this->riskProfile->verification_attempts < 3,
            ]),
        ];
    }
}
