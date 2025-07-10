<?php

namespace Ninja\Larasoul\Events;

use Ninja\Larasoul\Models\RiskProfile;

/**
 * Event fired when manual review is required
 */
class ManualReviewRequired extends RiskVerificationEvent
{
    public function __construct(
        public RiskProfile $riskProfile,
    ) {
        parent::__construct($riskProfile);
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            'admin.manual-review-queue',
            "user.{$this->riskProfile->user_id}.verification",
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'manual_review_required',
            'data' => array_merge($this->getRiskData(), [
                'priority' => $this->getReviewPriority(),
                'estimated_review_time' => $this->getEstimatedReviewTime(),
            ]),
        ];
    }

    /**
     * Get review priority
     */
    public function getReviewPriority(): string
    {
        $riskScore = $this->riskProfile->risk_score;

        if ($riskScore >= 0.8) {
            return 'high';
        } elseif ($riskScore >= 0.6) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get estimated review time
     */
    public function getEstimatedReviewTime(): string
    {
        return match ($this->getReviewPriority()) {
            'high' => '2-4 hours',
            'medium' => '4-8 hours',
            'low' => '1-2 business days',
        };
    }
}
