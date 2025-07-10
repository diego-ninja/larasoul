<?php

namespace Ninja\Larasoul\Events;

use Ninja\Larasoul\Models\RiskProfile;

/**
 * Event fired when fraud attempt is detected
 */
class FraudAttemptDetected extends RiskVerificationEvent
{
    public function __construct(
        public RiskProfile $riskProfile,
        public array $fraudIndicators = []
    ) {
        parent::__construct($riskProfile);
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            'admin.fraud-alerts',
            'admin.security-alerts',
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'fraud_attempt_detected',
            'data' => array_merge($this->getRiskData(), [
                'fraud_indicators' => $this->fraudIndicators,
                'severity' => $this->getFraudSeverity(),
                'immediate_action_required' => $this->requiresImmediateAction(),
            ]),
        ];
    }

    /**
     * Get fraud severity level
     */
    public function getFraudSeverity(): string
    {
        $riskScore = $this->riskProfile->risk_score;
        $hasBlockingFlags = $this->riskProfile->hasBlockingRiskFlags();

        if ($hasBlockingFlags || $riskScore >= 0.9) {
            return 'critical';
        } elseif ($riskScore >= 0.8) {
            return 'high';
        } elseif ($riskScore >= 0.6) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Check if requires immediate action
     */
    public function requiresImmediateAction(): bool
    {
        return $this->getFraudSeverity() === 'critical' ||
            $this->riskProfile->hasBlockingRiskFlags();
    }
}
