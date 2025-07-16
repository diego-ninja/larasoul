<?php

namespace Ninja\Larasoul\Enums;

enum RiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
    case Unknown = 'unknown';

    public static function values(): array
    {
        return [
            self::Low->value,
            self::Medium->value,
            self::High->value,
            self::Critical->value,
            self::Unknown->value,
        ];
    }

    public static function withScore(float $score): RiskLevel
    {
        if ($score >= config('larasoul.verification.risk_thresholds.critical')) {
            return self::Critical;
        } elseif ($score >= config('larasoul.verification.risk_thresholds.high')) {
            return self::High;
        } elseif ($score >= config('larasoul.verification.risk_thresholds.medium')) {
            return self::Medium;
        } elseif ($score > 0) {
            return self::Low;
        }

        return self::Unknown;
    }
}
