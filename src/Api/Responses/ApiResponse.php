<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Bag;
use Illuminate\Http\Resources\Json\JsonResource;

abstract readonly class ApiResponse extends Bag
{
    public function asResource(): JsonResource
    {
        return JsonResource::make($this->toArray());
    }
}
