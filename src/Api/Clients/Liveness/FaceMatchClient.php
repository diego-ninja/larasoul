<?php

namespace Ninja\Larasoul\Api\Clients\Liveness;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\Api\Responses\LivenessSessionResponse;
use Ninja\Larasoul\Api\Responses\VerifyFaceResponse;
use Ninja\Larasoul\Api\Responses\VerifyIdentityResponse;
use Ninja\Larasoul\Api\Contracts\FaceMatchInterface;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

final class FaceMatchClient extends LivenessApiClient implements FaceMatchInterface
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

        $response = $this->call(VerisoulApiEndpoint::FaceMatchSessionStart, $params);

        return LivenessSessionResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verify(string $sessionId): VerifyFaceResponse
    {
        $response = $this->call(
            endpoint: VerisoulApiEndpoint::VerifyFace,
            data: ['session_id' => $sessionId],
        );

        return VerifyFaceResponse::from($response);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verifyIdentity(string $sessionId, Authenticatable $user): VerifyIdentityResponse
    {
        $response = $this->call(
            endpoint: VerisoulApiEndpoint::VerifyIdentity,
            data: ['session_id' => $sessionId, 'account_id' => $user->getAuthIdentifier()],
        );

        return VerifyIdentityResponse::from($response);
    }
}
