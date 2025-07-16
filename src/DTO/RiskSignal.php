<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\Collection;
use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Collections\RiskSignalCollection;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\SignalScope;
use Ninja\Larasoul\ValueObjects\RiskScore;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
#[Collection(RiskSignalCollection::class)]
final readonly class RiskSignal extends Bag
{
    public function __construct(
        public string $name,
        public RiskScore $score,
        public SignalScope $scope = SignalScope::DeviceNetwork,
    ) {}

    /**
     * Check if signal is flagged (typically > 0.5)
     */
    public function isFlagged(float $threshold = 0.5): bool
    {
        return $this->score->value > $threshold;
    }

    /**
     * Check if signal is high risk (typically > 0.8)
     */
    public function isHighRisk(): bool
    {
        return $this->score->isHigh() || $this->score->isCritical();
    }

    /**
     * Get risk level based on score
     */
    public function getRiskLevel(): RiskLevel
    {
        return $this->score->level();
    }

    /**
     * Get display name for the signal
     */
    public function getDisplayName(): string
    {
        return match ($this->name) {
            // Device & Network signals (from DeviceNetworkSignals DTO)
            'device_risk' => 'Device Risk',
            'proxy' => 'Proxy',
            'vpn' => 'VPN',
            'datacenter' => 'Datacenter',
            'tor' => 'Tor',
            'spoofed_ip' => 'Spoofed IP',
            'recent_fraud_ip' => 'Recent Fraud IP',
            'device_network_mismatch' => 'Device Network Mismatch',
            'location_spoofing' => 'Location Spoofing',

            // Document signals (from DocumentSignals DTO)
            'id_age' => 'ID Age',
            'id_face_match_score' => 'ID Face Match Score',
            'id_barcode_status' => 'ID Barcode Status',
            'id_face_status' => 'ID Face Status',
            'id_text_status' => 'ID Text Status',
            'is_id_digital_spoof' => 'ID Digital Spoof',
            'is_full_id_captured' => 'Full ID Captured',
            'id_validity' => 'ID Validity',

            // Referring Session signals (from ReferringSessionSignals DTO)
            'impossible_travel' => 'Impossible Travel',
            'ip_mismatch' => 'IP Mismatch',
            'user_agent_mismatch' => 'User Agent Mismatch',
            'device_timezone_mismatch' => 'Device Timezone Mismatch',
            'ip_timezone_mismatch' => 'IP Timezone Mismatch',

            default => ucwords(str_replace('_', ' ', $this->name)),
        };
    }

    /**
     * Get description for the signal
     */
    public function getDescription(): string
    {
        return match ($this->name) {
            // Device & Network signals
            'device_risk' => 'Overall device risk assessment',
            'proxy' => 'Connection through proxy server detected',
            'vpn' => 'VPN usage detected',
            'datacenter' => 'Connection from datacenter IP address',
            'tor' => 'Connection through Tor network',
            'spoofed_ip' => 'IP address spoofing detected',
            'recent_fraud_ip' => 'IP address recently associated with fraud',
            'device_network_mismatch' => 'Device and network information mismatch',
            'location_spoofing' => 'Location spoofing detected',

            // Document signals
            'id_age' => 'Age of the identity document',
            'id_face_match_score' => 'Face match score between selfie and ID',
            'id_barcode_status' => 'Status of ID barcode verification',
            'id_face_status' => 'Status of face on ID document',
            'id_text_status' => 'Status of text on ID document',
            'is_id_digital_spoof' => 'Whether ID appears to be digitally spoofed',
            'is_full_id_captured' => 'Whether full ID was captured',
            'id_validity' => 'Overall validity of the ID document',

            // Referring Session signals
            'impossible_travel' => 'Impossible travel pattern detected',
            'ip_mismatch' => 'IP address mismatch between sessions',
            'user_agent_mismatch' => 'User agent mismatch between sessions',
            'device_timezone_mismatch' => 'Device timezone mismatch',
            'ip_timezone_mismatch' => 'IP timezone mismatch',

            default => 'Risk signal: '.$this->name,
        };
    }

    /**
     * Create RiskSignal from score
     */
    public static function fromScore(string $name, float $score): self
    {
        return new self(
            name: $name,
            score: RiskScore::from($score),
            scope: SignalScope::getScopeForSignal($name),
        );
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'score' => $this->score,
            'scope' => $this->scope->value,
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'risk_level' => $this->getRiskLevel(),
            'is_flagged' => $this->isFlagged(),
            'is_high_risk' => $this->isHighRisk(),
        ];
    }
}
