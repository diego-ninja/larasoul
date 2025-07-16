<?php

namespace Ninja\Larasoul\ValueObjects;

use Bag\Bag;
use Ninja\Larasoul\Enums\RiskLevel;

final readonly class RiskScore extends Bag
{
    public function __construct(
        public float $value
    ) {}

    public static function rules(): array
    {
        return [
            'value' => 'required|numeric|min:0|max:1',
        ];
    }

    public static function from(mixed ...$values): static
    {
        // Handle single value case (most common)
        if (count($values) === 1) {
            $value = $values[0];

            if ($value instanceof self) {
                return $value;
            }

            if (is_float($value) || is_int($value)) {
                return new self((float) $value);
            }

            // Handle Bag format (array with 'value' key)
            if (is_array($value) && isset($value['value'])) {
                return new self((float) $value['value']);
            }
        }

        // Fallback to parent Bag::from() for other cases
        return parent::from(...$values);
    }

    public static function low(): self
    {
        return new self(config('larasoul.verification.risk_thresholds.low'));
    }

    public static function medium(): self
    {
        return new self(config('larasoul.verification.risk_thresholds.medium'));
    }

    public static function high(): self
    {
        return new self(config('larasoul.verification.risk_thresholds.high'));
    }

    public static function critical(): self
    {
        return new self(config('larasoul.verification.risk_thresholds.critical'));
    }

    public function value(): float
    {
        return $this->value;
    }

    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    public function isOne(): bool
    {
        return $this->value === 1.0;
    }

    public function isBetween(float $min, float $max): bool
    {
        return $this->value >= $min && $this->value <= $max;
    }

    public function isPositive(): bool
    {
        return $this->value > 0;
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
