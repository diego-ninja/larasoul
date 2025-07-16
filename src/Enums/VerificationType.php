<?php

namespace Ninja\Larasoul\Enums;

enum VerificationType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Face = 'face';
    case Identity = 'identity';

    public static function values(): array
    {
        return [
            self::Email->value,
            self::Phone->value,
            self::Face->value,
            self::Identity->value,
        ];
    }

    public function getExpirationPeriod(): ?int
    {
        $configKey = sprintf('larasoul.verification.expirations.%s', $this->value);
        return config($configKey);
    }
}
