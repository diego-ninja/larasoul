<?php

namespace Ninja\Larasoul\Examples;

use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\DTO\DeviceNetworkSignals;
use Ninja\Larasoul\DTO\DocumentSignals;
use Ninja\Larasoul\DTO\ReferringSessionSignals;
use Ninja\Larasoul\Enums\IDBarcodeStatus;
use Ninja\Larasoul\Enums\IDDigitalSpoof;
use Ninja\Larasoul\Enums\IDFaceStatus;
use Ninja\Larasoul\Enums\IDStatus;
use Ninja\Larasoul\Enums\IDTextStatus;
use Ninja\Larasoul\Enums\IDValidity;
use Ninja\Larasoul\Enums\SignalScope;

/**
 * Example showing integration with actual Verisoul signal DTOs
 */
class VerisoulSignalIntegrationExample
{
    /**
     * Example 1: Creating RiskSignalCollection from actual Verisoul DeviceNetworkSignals DTO
     */
    public function createFromDeviceNetworkSignals(): RiskSignalCollection
    {
        // Simulate a DeviceNetworkSignals DTO response from Verisoul API
        $deviceNetworkSignals = new DeviceNetworkSignals(
            deviceRisk: 0.75,
            proxy: 0.85,
            vpn: 0.0,
            datacenter: 0.0,
            tor: 0.0,
            spoofedIp: 0.0,
            recentFraudIp: 0.3,
            deviceNetworkMismatch: 0.1,
            locationSpoofing: 0.9
        );

        // Convert to our RiskSignalCollection
        $collection = RiskSignalCollection::fromDeviceNetworkSignals($deviceNetworkSignals);

        echo "Created collection from DeviceNetworkSignals:\n";
        echo 'Total signals: '.$collection->count()."\n";
        echo 'Flagged signals: '.$collection->flagged()->count()."\n";
        echo 'High risk signals: '.$collection->highRisk()->count()."\n";

        return $collection;
    }

    /**
     * Example 2: Creating from DocumentSignals DTO
     */
    public function createFromDocumentSignals(): RiskSignalCollection
    {
        // Simulate a DocumentSignals DTO response from Verisoul API
        $documentSignals = new DocumentSignals(
            idAge: 25,
            idFaceMatchScore: 0.95,
            idBarcodeStatus: IDBarcodeStatus::Valid,
            idFaceStatus: IDFaceStatus::Valid,
            idTextStatus: IDTextStatus::Valid,
            isIdDigitalSpoof: IDDigitalSpoof::No,
            isFullIdCaptured: IDStatus::Yes,
            idValidity: IDValidity::Valid
        );

        // Convert to our RiskSignalCollection (only numeric scores are included)
        $collection = RiskSignalCollection::fromDocumentSignals($documentSignals);

        echo "Created collection from DocumentSignals:\n";
        echo 'Total signals: '.$collection->count()."\n";
        echo 'Document scope signals: '.$collection->byScope(SignalScope::Document)->count()."\n";

        return $collection;
    }

    /**
     * Example 3: Creating from ReferringSessionSignals DTO
     */
    public function createFromReferringSessionSignals(): RiskSignalCollection
    {
        // Simulate a ReferringSessionSignals DTO response from Verisoul API
        $referringSessionSignals = new ReferringSessionSignals(
            impossibleTravel: 0.8,
            ipMismatch: 0.2,
            userAgentMismatch: 0.0,
            deviceTimezoneMismatch: 0.1,
            ipTimezoneMismatch: 0.0
        );

        // Convert to our RiskSignalCollection
        $collection = RiskSignalCollection::fromReferringSessionSignals($referringSessionSignals);

        echo "Created collection from ReferringSessionSignals:\n";
        echo 'Total signals: '.$collection->count()."\n";
        echo 'Referring session scope signals: '.$collection->byScope(SignalScope::ReferringSession)->count()."\n";

        return $collection;
    }

    /**
     * Example 4: Creating comprehensive collection from all Verisoul signal DTOs
     */
    public function createComprehensiveCollection(): RiskSignalCollection
    {
        // Simulate all three types of signals from a comprehensive Verisoul response
        $deviceNetworkSignals = new DeviceNetworkSignals(
            deviceRisk: 0.3,
            proxy: 0.0,
            vpn: 0.0,
            datacenter: 0.0,
            tor: 0.0,
            spoofedIp: 0.0,
            recentFraudIp: 0.0,
            deviceNetworkMismatch: 0.0,
            locationSpoofing: 0.0
        );

        $documentSignals = new DocumentSignals(
            idAge: 30,
            idFaceMatchScore: 0.98,
            idBarcodeStatus: IDBarcodeStatus::Valid,
            idFaceStatus: IDFaceStatus::Valid,
            idTextStatus: IDTextStatus::Valid,
            isIdDigitalSpoof: IDDigitalSpoof::No,
            isFullIdCaptured: IDStatus::Yes,
            idValidity: IDValidity::Valid
        );

        $referringSessionSignals = new ReferringSessionSignals(
            impossibleTravel: 0.0,
            ipMismatch: 0.0,
            userAgentMismatch: 0.0,
            deviceTimezoneMismatch: 0.0,
            ipTimezoneMismatch: 0.0
        );

        // Create comprehensive collection
        $collection = RiskSignalCollection::fromVerisoulSignals(
            $deviceNetworkSignals,
            $documentSignals,
            $referringSessionSignals
        );

        return $collection;
    }

    /**
     * Example 5: Analyzing the comprehensive collection
     */
    public function analyzeComprehensiveCollection(): void
    {
        $collection = $this->createComprehensiveCollection();

        echo "=== Comprehensive Verisoul Signal Analysis ===\n";
        echo 'Total signals: '.$collection->count()."\n";

        // Analyze by scope
        echo "\n--- Signals by Scope ---\n";
        foreach (SignalScope::cases() as $scope) {
            $scopeSignals = $collection->byScope($scope);
            if ($scopeSignals->count() > 0) {
                echo $scope->getDisplayName().': '.$scopeSignals->count()." signals\n";
            }
        }

        // Overall risk assessment
        echo "\n--- Risk Assessment ---\n";
        echo 'Overall risk score: '.number_format($collection->getOverallRiskScore(), 3)."\n";
        echo 'Weighted risk score: '.number_format($collection->getWeightedRiskScore(), 3)."\n";
        echo 'Has flagged signals: '.($collection->hasFlaggedSignals() ? 'Yes' : 'No')."\n";
        echo 'Has high risk signals: '.($collection->hasHighRiskSignals() ? 'Yes' : 'No')."\n";

        // Most critical signals
        $critical = $collection->getMostCritical(3);
        echo "\n--- Most Critical Signals ---\n";
        foreach ($critical as $signal) {
            echo '- '.$signal->getDisplayName().' ('.$signal->scope->getDisplayName().'): '.
                 number_format($signal->score, 3)."\n";
        }

        // Summary by risk level
        echo "\n--- Signals by Risk Level ---\n";
        $byRiskLevel = $collection->groupedByRiskLevel();
        foreach ($byRiskLevel as $level => $signals) {
            echo ucfirst($level).' risk: '.count($signals)." signals\n";
        }
    }

    /**
     * Example 6: Custom risk scoring based on verification type
     */
    public function customRiskScoringExample(): void
    {
        $collection = $this->createComprehensiveCollection();

        // Custom weights for ID verification
        $idVerificationWeights = [
            SignalScope::Document->value => 0.6,        // Prioritize document signals
            SignalScope::DeviceNetwork->value => 0.25,  // Device/network secondary
            SignalScope::ReferringSession->value => 0.15, // Session context tertiary
        ];

        // Custom weights for login/session validation
        $loginValidationWeights = [
            SignalScope::DeviceNetwork->value => 0.5,   // Prioritize device/network
            SignalScope::ReferringSession->value => 0.4, // Session context important
            SignalScope::Document->value => 0.1,        // Document less relevant for login
        ];

        echo "=== Custom Risk Scoring Examples ===\n";
        echo 'Standard weighted score: '.number_format($collection->getWeightedRiskScore(), 3)."\n";
        echo 'ID Verification weighted score: '.number_format($collection->getWeightedRiskScore($idVerificationWeights), 3)."\n";
        echo 'Login Validation weighted score: '.number_format($collection->getWeightedRiskScore($loginValidationWeights), 3)."\n";
    }

    /**
     * Example 7: Signal filtering and analysis
     */
    public function signalFilteringExample(): void
    {
        // Create a more risky scenario
        $deviceNetworkSignals = new DeviceNetworkSignals(
            deviceRisk: 0.85,
            proxy: 0.9,
            vpn: 0.0,
            datacenter: 0.7,
            tor: 0.0,
            spoofedIp: 0.3,
            recentFraudIp: 0.8,
            deviceNetworkMismatch: 0.6,
            locationSpoofing: 0.95
        );

        $collection = RiskSignalCollection::fromDeviceNetworkSignals($deviceNetworkSignals);

        echo "=== Signal Filtering Examples ===\n";
        echo 'Total signals: '.$collection->count()."\n";

        // Get specific types of signals
        $flaggedSignals = $collection->flagged(0.5);
        echo 'Flagged signals (> 0.5): '.$flaggedSignals->count()."\n";

        $highRiskSignals = $collection->highRisk(0.8);
        echo 'High risk signals (> 0.8): '.$highRiskSignals->count()."\n";

        // Analyze specific signals
        echo "\n--- High Risk Signal Details ---\n";
        foreach ($highRiskSignals as $signal) {
            echo '- '.$signal->getDisplayName().': '.number_format($signal->score, 3)."\n";
            echo '  '.$signal->getDescription()."\n";
        }

        // Network-specific analysis
        $networkSignals = $collection->byScope(SignalScope::DeviceNetwork);
        echo "\nDevice & Network signals analysis:\n";
        echo 'Average score: '.number_format($networkSignals->avg('score'), 3)."\n";
        echo 'Max score: '.number_format($networkSignals->max('score'), 3)."\n";
        echo 'Min score: '.number_format($networkSignals->min('score'), 3)."\n";
    }

    /**
     * Run all examples
     */
    public function runAllExamples(): void
    {
        echo "=== Verisoul Signal Integration Examples ===\n\n";

        $this->createFromDeviceNetworkSignals();
        echo "\n";

        $this->createFromDocumentSignals();
        echo "\n";

        $this->createFromReferringSessionSignals();
        echo "\n";

        $this->analyzeComprehensiveCollection();
        echo "\n";

        $this->customRiskScoringExample();
        echo "\n";

        $this->signalFilteringExample();
    }
}
