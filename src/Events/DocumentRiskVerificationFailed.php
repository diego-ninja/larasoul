<?php

namespace Ninja\Larasoul\Events;

use Ninja\Larasoul\Models\RiskProfile;

/**
 * Event fired when document verification fails
 */
class DocumentRiskVerificationFailed extends RiskVerificationEvent
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
            'type' => 'document_verification_failed',
            'data' => array_merge($this->getRiskData(), [
                'failure_reason' => $this->reason,
                'document_type' => $this->riskProfile->document_type,
                'document_country' => $this->riskProfile->document_country_code,
                'can_retry' => $this->riskProfile->verification_attempts < 3,
            ]),
        ];
    }
}
