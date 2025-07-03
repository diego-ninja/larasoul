<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Enums\VerisoulEnvironment;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class LivenessSessionResponse extends ApiResponse
{
    public function __construct(
        public string $requestId,
        public string $sessionId,
    ) {}

    public function redirectUrl(VerisoulEnvironment $environment = VerisoulEnvironment::Sandbox): string
    {
        return sprintf('https://app.%s.verisoul.ai/?session_id=%s', $environment->value, $this->sessionId);
    }
}
