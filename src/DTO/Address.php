<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class Address extends Bag
{
    public function __construct(
        public ?string $city,
        public ?string $country,
        public ?string $postalCode,
        public ?string $state,
        public ?string $street,
    ) {}

    /**
     * Check if address has any data
     */
    public function hasData(): bool
    {
        return ! empty($this->city) || ! empty($this->country) || ! empty($this->postalCode) ||
               ! empty($this->state) || ! empty($this->street);
    }

    /**
     * Check if address is complete
     */
    public function isComplete(): bool
    {
        return ! empty($this->street) && ! empty($this->city) &&
               ! empty($this->state) && ! empty($this->postalCode) && ! empty($this->country);
    }

    /**
     * Get formatted address string
     */
    public function getFormattedAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): float
    {
        $fields = [$this->street, $this->city, $this->state, $this->postalCode, $this->country];
        $filledFields = count(array_filter($fields, fn($field) => ! empty($field)));

        return round(($filledFields / count($fields)) * 100, 1);
    }
}
