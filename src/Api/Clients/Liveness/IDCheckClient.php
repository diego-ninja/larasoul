<?php

namespace Ninja\Larasoul\Api\Clients\Liveness;

use Ninja\Larasoul\Api\Responses\LivenessSessionResponse;
use Ninja\Larasoul\Api\Responses\VerifyIdResponse;
use Ninja\Larasoul\Contracts\IDCheckInterface;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

final class IDCheckClient extends LivenessApiClient implements IDCheckInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function session(?string $referringSessionId = null): ?LivenessSessionResponse
    {
        $params = $referringSessionId !== null ?
            ['referring_session_id' => $referringSessionId] :
            [];

        $response = $this->call(VerisoulApiEndpoint::IDCheckSessionStart, array_merge($params, ['id' => 'true']));

        return LivenessSessionResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verify(string $sessionId): VerifyIdResponse
    {
        $response = $this->call(
            endpoint: VerisoulApiEndpoint::VerifyId,
            data: ['session_id' => $sessionId],
        );

        return VerifyIdResponse::from($response);
    }
}
