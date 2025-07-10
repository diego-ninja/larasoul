<?php

namespace Ninja\Larasoul\Enums;

enum RiskFlag: string
{
    // Device Risk Flags
    case HighDeviceRisk = 'high_device_risk';
    case RepeatDevice = 'repeat_device';

    // Network Risk Flags
    case ProxyDetected = 'proxy_detected';
    case VpnDetected = 'vpn_detected';
    case DatacenterDetected = 'datacenter_detected';
    case RecentFraudIp = 'recent_fraud_ip';

    // ID Fraud Risk Flags
    case CannotConfirmIdIsAuthentic = 'cannot_confirm_id_is_authentic';
    case LikelyFakeId = 'likely_fake_id';
    case IdExpired = 'id_expired';
    case IdAgeBelow16 = 'id_age_below_16';

    // Face Match Risk Flags
    case LowIdFaceMatchScore = 'low_id_face_match_score';
    case ModerateIdFaceMatchScore = 'moderate_id_face_match_score';

    // Multi-Accounting Risk Flags
    case RepeatFace = 'repeat_face';
    case RepeatId = 'repeat_id';

    // Fraud Farm Risk Flags
    case KnownFraudFace = 'known_fraud_face';
    case KnownFraudId = 'known_fraud_id';

    // ID Selling Risk Flags
    case ImpossibleTravelDetected = 'impossible_travel_detected';
    case IpDocumentCountryMismatch = 'ip_document_country_mismatch';
    case LocationSpoofing = 'location_spoofing';

    /**
     * Get all possible risk flag values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Get flags by category
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(self::cases(), fn (RiskFlag $flag) => $flag->getCategory() === $category);
    }

    /**
     * Get flags by risk level
     */
    public static function getByRiskLevel(RiskLevel $level): array
    {
        return array_filter(self::cases(), fn (RiskFlag $flag) => $flag->getRiskLevel() === $level);
    }

    /**
     * Get blocking flags
     */
    public static function getBlockingFlags(): array
    {
        return array_filter(self::cases(), fn (RiskFlag $flag) => $flag->shouldBlock());
    }

    /**
     * Get display name for risk flag
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            // Device flags
            self::HighDeviceRisk => 'High Device Risk',
            self::RepeatDevice => 'Repeat Device',

            // Network flags
            self::ProxyDetected => 'Proxy Detected',
            self::VpnDetected => 'VPN Detected',
            self::DatacenterDetected => 'Datacenter Detected',
            self::RecentFraudIp => 'Recent Fraud IP',

            // ID Fraud flags
            self::CannotConfirmIdIsAuthentic => 'Cannot Confirm ID is Authentic',
            self::LikelyFakeId => 'Likely Fake ID',
            self::IdExpired => 'ID Expired',
            self::IdAgeBelow16 => 'ID Age Below 16',

            // Face Match flags
            self::LowIdFaceMatchScore => 'Low ID Face Match Score',
            self::ModerateIdFaceMatchScore => 'Moderate ID Face Match Score',

            // Multi-Accounting flags
            self::RepeatFace => 'Repeat Face',
            self::RepeatId => 'Repeat ID',

            // Fraud Farm flags
            self::KnownFraudFace => 'Known Fraud Face',
            self::KnownFraudId => 'Known Fraud ID',

            // ID Selling flags
            self::ImpossibleTravelDetected => 'Impossible Travel Detected',
            self::IpDocumentCountryMismatch => 'IP Document Country Mismatch',
            self::LocationSpoofing => 'Location Spoofing',
        };
    }

    /**
     * Get description for risk flag
     */
    public function getDescription(): string
    {
        return match ($this) {
            // Device flags
            self::HighDeviceRisk => 'Device likely emulator, VM',
            self::RepeatDevice => 'Device has been used for multiple ID Checks',

            // Network flags
            self::ProxyDetected => 'ID Check on a proxy IP',
            self::VpnDetected => 'ID Check on a VPN',
            self::DatacenterDetected => 'ID Check on a datacenter IP',
            self::RecentFraudIp => 'IP recently reported as fraud',

            // ID Fraud flags
            self::CannotConfirmIdIsAuthentic => 'ID may be spoofed or digital media',
            self::LikelyFakeId => 'ID very likely fake',
            self::IdExpired => 'ID Expiration date is past',
            self::IdAgeBelow16 => 'DOB on ID indicates user is below 16 years old',

            // Face Match flags
            self::LowIdFaceMatchScore => 'Face does not match ID Photo',
            self::ModerateIdFaceMatchScore => 'Face may not match ID photo',

            // Multi-Accounting flags
            self::RepeatFace => 'Face has been seen in your application under a different account',
            self::RepeatId => 'ID has been seen in your application under a different account',

            // Fraud Farm flags
            self::KnownFraudFace => 'Face is associated with fraud',
            self::KnownFraudId => 'ID is associated with fraud',

            // ID Selling flags
            self::ImpossibleTravelDetected => 'Referring session geolocation is far from ID Check geolocation',
            self::IpDocumentCountryMismatch => 'Current IP geolocation country does not match document geolocation',
            self::LocationSpoofing => 'User is actively trying to obfuscate their current location',
        };
    }

    /**
     * Get category for this risk flag
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::HighDeviceRisk,
            self::RepeatDevice => 'device',

            self::ProxyDetected,
            self::VpnDetected,
            self::DatacenterDetected,
            self::RecentFraudIp => 'network',

            self::CannotConfirmIdIsAuthentic,
            self::LikelyFakeId,
            self::IdExpired,
            self::IdAgeBelow16 => 'id_fraud',

            self::LowIdFaceMatchScore,
            self::ModerateIdFaceMatchScore => 'face_match',

            self::RepeatFace,
            self::RepeatId => 'multi_accounting',

            self::KnownFraudFace,
            self::KnownFraudId => 'fraud_farm',

            self::ImpossibleTravelDetected,
            self::IpDocumentCountryMismatch,
            self::LocationSpoofing => 'id_selling',
        };
    }

    /**
     * Get risk level for this flag
     */
    public function getRiskLevel(): RiskLevel
    {
        return match ($this) {
            // High risk flags
            self::LikelyFakeId,
            self::KnownFraudFace,
            self::KnownFraudId,
            self::LowIdFaceMatchScore => RiskLevel::High,

            // Medium risk flags
            self::CannotConfirmIdIsAuthentic,
            self::HighDeviceRisk,
            self::ModerateIdFaceMatchScore,
            self::RepeatFace,
            self::RepeatId,
            self::RecentFraudIp,
            self::ImpossibleTravelDetected,
            self::LocationSpoofing => RiskLevel::Medium,

            // Low risk flags
            self::IdExpired,
            self::IdAgeBelow16,
            self::RepeatDevice,
            self::ProxyDetected,
            self::VpnDetected,
            self::DatacenterDetected,
            self::IpDocumentCountryMismatch => RiskLevel::Low,
        };
    }

    /**
     * Check if this flag should block verification
     */
    public function shouldBlock(): bool
    {
        return match ($this) {
            self::LikelyFakeId,
            self::KnownFraudFace,
            self::KnownFraudId,
            self::LowIdFaceMatchScore => true,
            default => false,
        };
    }
}
