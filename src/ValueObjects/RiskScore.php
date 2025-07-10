<?php

namespace Ninja\Larasoul\ValueObjects;

use Ninja\Larasoul\Enums\RiskLevel;

final readonly class RiskScore
{
    public function __construct(
        private float $value
    ) {
        if ($this->value < 0.0 || $this->value > 1.0) {
            throw new \InvalidArgumentException('Risk score must be between 0.0 and 1.0');
        }
    }

    public static function from(float $value): self
    {
        return new self($value);
    }

    public static function low(): self
    {
        return new self(0.1);
    }

    public static function medium(): self
    {
        return new self(0.5);
    }

    public static function high(): self
    {
        return new self(0.9);
    }

    public static function critical(): self
    {
        return new self(1.0);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function level(): RiskLevel
    {
        return match (true) {
            $this->value <= 0.3 => RiskLevel::Low,
            $this->value <= 0.7 => RiskLevel::Medium,
            $this->value <= 0.9 => RiskLevel::High,
            default => RiskLevel::Critical,
        };
    }

    public function isLow(): bool
    {
        return $this->level() === RiskLevel::Low;
    }

    public function isMedium(): bool
    {
        return $this->level() === RiskLevel::Medium;
    }

    public function isHigh(): bool
    {
        return $this->level() === RiskLevel::High;
    }

    public function isCritical(): bool
    {
        return $this->level() === RiskLevel::Critical;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
