<?php

namespace Ninja\Larasoul\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Ninja\Larasoul\Collections\RiskSignalCollection;

class RiskSignalCollectionCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): RiskSignalCollection
    {
        if ($value === null) {
            return new RiskSignalCollection;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return new RiskSignalCollection;
        }

        return RiskSignalCollection::fromArray($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value === null) {
            return json_encode([]);
        }

        if ($value instanceof RiskSignalCollection) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return json_encode([]);
    }
}
