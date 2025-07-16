<?php

namespace Ninja\Larasoul\Examples;

use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\DTO\RiskSignal;
use Ninja\Larasoul\DTO\RiskSignals;
use Ninja\Larasoul\DTO\RiskSignalScore;
use Ninja\Larasoul\Enums\SignalScope;
use Ninja\Larasoul\Models\RiskProfile;
use Ninja\Larasoul\Models\UserVerification;

class RiskSignalUsageExample
{
    /**
     * Example 1: Creating a RiskSignalCollection from individual Verisoul signals
     */
    public function createFromIndividualSignals(): RiskSignalCollection
    {
        $collection = new RiskSignalCollection;

        // Add actual Verisoul signals
        $collection->addSignal('proxy', 0.8, 0.3, SignalScope::DeviceNetwork);
        $collection->addSignal('device_risk', 0.6, 0.2, SignalScope::DeviceNetwork);
        $collection->addSignal('location_spoofing', 0.9, 0.1, SignalScope::DeviceNetwork);
        $collection->addSignal('impossible_travel', 0.7, 0.05, SignalScope::ReferringSession);
        $collection->addSignal('id_face_match_score', 0.95, 0.9, SignalScope::Document);

        return $collection;
    }

    /**
     * Example 2: Creating from legacy DTOs (for backward compatibility)
     */
    public function createFromLegacyDTOs(): RiskSignalCollection
    {
        // Simulating legacy DTOs
        $signals = new RiskSignals(
            deviceRisk: true,
            proxy: true,
            vpn: false,
            datacenter: false,
            tor: false,
            spoofedIp: false,
            recentFraudIp: false,
            impossibleTravel: false,
            deviceNetworkMismatch: false,
            locationSpoofing: true,
        );

        $scores = new RiskSignalScore(
            deviceRisk: 0.8,
            proxy: 0.9,
            vpn: 0.0,
            datacenter: 0.0,
            tor: 0.0,
            spoofedIp: 0.0,
            recentFraudIp: 0.0,
            impossibleTravel: 0.0,
            deviceNetworkMismatch: 0.0,
            locationSpoofing: 0.85,
        );

        $averages = new RiskSignalScore(
            deviceRisk: 0.3,
            proxy: 0.2,
            vpn: 0.1,
            datacenter: 0.05,
            tor: 0.01,
            spoofedIp: 0.02,
            recentFraudIp: 0.03,
            impossibleTravel: 0.01,
            deviceNetworkMismatch: 0.02,
            locationSpoofing: 0.1,
        );

        return RiskSignalCollection::fromLegacyDTOs($signals, $scores, $averages);
    }

    /**
     * Example 3: Working with the collection using real Verisoul signal scopes
     */
    public function workWithCollection(): void
    {
        $collection = $this->createFromLegacyDTOs();

        // Get flagged signals
        $flaggedSignals = $collection->flagged();
        echo 'Flagged signals: '.$flaggedSignals->count()."\n";

        // Get high risk signals
        $highRiskSignals = $collection->highRisk();
        echo 'High risk signals: '.$highRiskSignals->count()."\n";

        // Get signals by actual Verisoul scopes
        $deviceNetworkSignals = $collection->byScope(SignalScope::DeviceNetwork);
        echo 'Device & Network signals: '.$deviceNetworkSignals->count()."\n";

        $documentSignals = $collection->byScope(SignalScope::Document);
        echo 'Document signals: '.$documentSignals->count()."\n";

        $referringSessionSignals = $collection->byScope(SignalScope::ReferringSession);
        echo 'Referring Session signals: '.$referringSessionSignals->count()."\n";

        // Get signals above average
        $aboveAverageSignals = $collection->aboveAverage();
        echo 'Above average signals: '.$aboveAverageSignals->count()."\n";

        // Get overall risk score
        $overallRisk = $collection->getOverallRiskScore();
        echo 'Overall risk score: '.$overallRisk."\n";

        // Get weighted risk score
        $weightedRisk = $collection->getWeightedRiskScore();
        echo 'Weighted risk score: '.$weightedRisk."\n";

        // Get summary
        $summary = $collection->getSummary();
        echo 'Summary: '.json_encode($summary, JSON_PRETTY_PRINT)."\n";

        // Get most critical signals
        $criticalSignals = $collection->getMostCritical(3);
        echo 'Most critical signals: '.$criticalSignals->count()."\n";
    }

    /**
     * Example 4: Using with models
     */
    public function useWithModels(): void
    {
        // Create a user and risk profile
        $user = new \App\Models\User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        // Create risk profile with signals
        $riskProfile = new RiskProfile;
        $riskProfile->user_id = $user->id;
        $riskProfile->risk_score = 0.7;

        // The risk_signals will be automatically cast to RiskSignalCollection
        $riskProfile->risk_signals = [
            [
                'name' => 'proxy',
                'score' => 0.8,
                'average' => 0.3,
                'scope' => SignalScope::DeviceNetwork->value,
            ],
            [
                'name' => 'device_risk',
                'score' => 0.6,
                'average' => 0.2,
                'scope' => SignalScope::DeviceNetwork->value,
            ],
        ];

        // Working with the risk signals
        $signals = $riskProfile->risk_signals; // This is now a RiskSignalCollection

        echo 'Has flagged signals: '.($signals->hasFlaggedSignals() ? 'Yes' : 'No')."\n";
        echo 'Device & Network signals count: '.$signals->byScope(SignalScope::DeviceNetwork)->count()."\n";
        echo 'Overall risk: '.$signals->getOverallRiskScore()."\n";
    }

    /**
     * Example 5: Individual signal analysis using actual Verisoul signals
     */
    public function analyzeIndividualSignals(): void
    {
        $signal = new RiskSignal(
            name: 'proxy',
            score: 0.8,
            average: 0.3,
            scope: SignalScope::DeviceNetwork
        );

        echo 'Signal: '.$signal->getDisplayName()."\n";
        echo 'Description: '.$signal->getDescription()."\n";
        echo 'Risk Level: '.$signal->getRiskLevel()."\n";
        echo 'Is Flagged: '.($signal->isFlagged() ? 'Yes' : 'No')."\n";
        echo 'Is High Risk: '.($signal->isHighRisk() ? 'Yes' : 'No')."\n";
        echo 'Is Above Average: '.($signal->isAboveAverage() ? 'Yes' : 'No')."\n";
        echo 'Deviation from Average: '.$signal->getDeviationFromAverage()."\n";
        echo 'Percentage Deviation: '.$signal->getPercentageDeviationFromAverage()."%\n";
    }

    /**
     * Example 6: Working with verification signals
     */
    public function workWithVerificationSignals(): void
    {
        $verification = new UserVerification;
        $verification->type = \Ninja\Larasoul\Enums\VerificationType::Face;
        $verification->status = \Ninja\Larasoul\Enums\VerificationStatus::Verified;
        $verification->risk_score = 0.2;

        // Add actual Verisoul verification signals
        $verification->risk_signals = [
            [
                'name' => 'id_face_match_score',
                'score' => 0.95, // High score = good match
                'average' => 0.85,
                'scope' => SignalScope::Document->value,
            ],
            [
                'name' => 'device_risk',
                'score' => 0.2,
                'average' => 0.3,
                'scope' => SignalScope::DeviceNetwork->value,
            ],
        ];

        $signals = $verification->risk_signals;
        echo 'Document signals: '.$signals->byScope(SignalScope::Document)->count()."\n";
        echo 'Device & Network signals: '.$signals->byScope(SignalScope::DeviceNetwork)->count()."\n";
        echo 'Face verification risk: '.$signals->getOverallRiskScore()."\n";
    }

    /**
     * Example 7: Custom scope weights
     */
    public function customScopeWeights(): void
    {
        $collection = $this->createFromLegacyDTOs();

        // Custom weights using actual Verisoul signal scopes
        $customWeights = [
            SignalScope::Document->value => 0.4,
            SignalScope::DeviceNetwork->value => 0.3,
            SignalScope::ReferringSession->value => 0.2,
            SignalScope::Account->value => 0.05,
            SignalScope::Session->value => 0.05,
        ];

        $weightedScore = $collection->getWeightedRiskScore($customWeights);
        echo 'Custom weighted score: '.$weightedScore."\n";
    }

    /**
     * Example 8: Filtering and analysis
     */
    public function filteringAndAnalysis(): void
    {
        $collection = $this->createFromLegacyDTOs();

        // Get significant deviations (signals 50% above average)
        $significantDeviations = $collection->getSignificantDeviations(50.0);
        echo 'Significant deviations: '.$significantDeviations->count()."\n";

        // Group by risk level
        $byRiskLevel = $collection->groupedByRiskLevel();
        foreach ($byRiskLevel as $riskLevel => $signals) {
            echo "Risk level '{$riskLevel}': ".count($signals)." signals\n";
        }

        // Group by scope
        $byScope = $collection->groupedByScope();
        foreach ($byScope as $scope => $signals) {
            echo "Scope '{$scope}': ".count($signals)." signals\n";
        }
    }

    /**
     * Example 9: Converting back to legacy format
     */
    public function convertToLegacyFormat(): void
    {
        $collection = $this->createFromLegacyDTOs();

        // Convert back to legacy boolean format
        $legacySignals = $collection->toLegacyRiskSignals();
        echo 'Legacy signals: '.json_encode($legacySignals, JSON_PRETTY_PRINT)."\n";

        // Convert back to legacy score format
        $legacyScores = $collection->toLegacyRiskSignalScores();
        echo 'Legacy scores: '.json_encode($legacyScores, JSON_PRETTY_PRINT)."\n";
    }
}
