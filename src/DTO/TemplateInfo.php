<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class TemplateInfo extends Bag
{
    public function __construct(
        public string $documentCountryCode,
        public ?string $documentState,
        public string $templateType,
    ) {}
}
