<?php

namespace Ninja\Larasoul\Enums;

enum SecurityLevel: string
{
    case None = 'none';
    case Basic = 'basic';
    case Standard = 'standard';
    case Premium = 'premium';
    case Enterprise = 'enterprise';

    public static function values(): array
    {
        return [
            self::None->value,
            self::Basic->value,
            self::Standard->value,
            self::Premium->value,
            self::Enterprise->value,
        ];
    }

    /**
     * Returns the security level as a string.
     * @return VerificationType<string>[]
     */
    public function getVerificationRequirements(): array
    {
        return match ($this) {
            self::Basic => [
                VerificationType::Email,
            ],
            self::Standard => [
                VerificationType::Email,
                VerificationType::Phone,
            ],
            self::Premium => [
                VerificationType::Email,
                VerificationType::Phone,
                VerificationType::Face,
            ],
            self::Enterprise => [
                VerificationType::Email,
                VerificationType::Phone,
                VerificationType::Face,
                VerificationType::Identity,
            ],
        };
    }
}
