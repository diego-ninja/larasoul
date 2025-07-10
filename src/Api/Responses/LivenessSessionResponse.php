<?php

namespace Ninja\Larasoul\Api\Responses;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Mappers\SnakeCase;
use Illuminate\Http\Resources\Json\JsonResource;
use Ninja\Larasoul\Enums\VerisoulEnvironment;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class LivenessSessionResponse extends ApiResponse
{
    public function __construct(
        public string $requestId,
        public string $sessionId,
    ) {}

    public function redirectUrl(VerisoulEnvironment $environment = VerisoulEnvironment::Sandbox, ?string $redirectUrl = null): string
    {
        $url = sprintf(
            'https://app.%s.verisoul.ai/?session_id=%s',
            $environment->value,
            $this->sessionId
        );

        if ($redirectUrl) {
            return sprintf('%s&redirect_url=%s', $url, urlencode($redirectUrl));
        }

        return $url;
    }

    public function asResource(): JsonResource
    {
        $environment = VerisoulEnvironment::from(config('larasoul.verisoul.environment'));

        return JsonResource::make([
            'request_id' => $this->requestId,
            'session_id' => $this->sessionId,
            'redirect_url' => $this->redirectUrl($environment),
            'environment' => $environment->value,
        ]);
    }
}
