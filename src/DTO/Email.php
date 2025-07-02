<?php

namespace Ninja\Larasoul\DTO;

use Bag\Bag;

final readonly class Email extends Bag
{
    public function __construct(
        public string $email,
        public bool $personal,
        public bool $disposable,
        public bool $valid,
    ) {}
}
