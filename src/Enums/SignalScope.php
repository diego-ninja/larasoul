<?php

namespace Ninja\Larasoul\Enums;

enum SignalScope: string
{
    // Based on actual Verisoul signal categories
    case DeviceNetwork = 'device_network';
    case Document = 'document';
    case ReferringSession = 'referring_session';
    case Account = 'account';
    case Session = 'session';

    /**
     * Get all enum values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all enum names
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Get display name for the scope
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::DeviceNetwork => 'Device & Network Signals',
            self::Document => 'Document Signals',
            self::ReferringSession => 'Referring Session Signals',
            self::Account => 'Account Signals',
            self::Session => 'Session Signals',
        };
    }

    /**
     * Get description for the scope
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DeviceNetwork => 'Device fingerprinting, network risk, proxy/VPN detection, and location spoofing',
            self::Document => 'ID document authenticity, validity, face matching, and document-specific signals',
            self::ReferringSession => 'Cross-session analysis including impossible travel and session mismatches',
            self::Account => 'Account-level risk assessment and persistent identity signals',
            self::Session => 'Session-level risk assessment and temporary interaction signals',
        };
    }

    /**
     * Get color associated with the scope (for UI purposes)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DeviceNetwork => 'red',
            self::Document => 'blue',
            self::ReferringSession => 'orange',
            self::Account => 'green',
            self::Session => 'purple',
        };
    }

    /**
     * Get appropriate scope for signal name based on actual Verisoul DTOs
     */
    public static function getScopeForSignal(string $name): SignalScope
    {
        return match ($name) {
            // DeviceNetworkSignals DTO fields
            'device_risk', 'proxy', 'vpn', 'datacenter', 'tor', 'spoofed_ip',
            'recent_fraud_ip', 'device_network_mismatch', 'location_spoofing' => SignalScope::DeviceNetwork,

            // DocumentSignals DTO fields
            'id_age', 'id_face_match_score', 'id_barcode_status', 'id_face_status',
            'id_text_status', 'is_id_digital_spoof', 'is_full_id_captured', 'id_validity' => SignalScope::Document,

            // ReferringSessionSignals DTO fields
            'impossible_travel', 'ip_mismatch', 'user_agent_mismatch',
            'device_timezone_mismatch', 'ip_timezone_mismatch' => SignalScope::ReferringSession,

            // Account-level signals
            'account_score', 'multi_accounting', 'bot' => SignalScope::Account,

            // Session-level signals
            'session_risk' => SignalScope::Session,

            default => SignalScope::DeviceNetwork,
        };
    }
}
