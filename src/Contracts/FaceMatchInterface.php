<?php

namespace Ninja\Larasoul\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\Api\Responses\VerifyFaceResponse;
use Ninja\Larasoul\Api\Responses\VerifyIdentityResponse;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

interface FaceMatchInterface extends BiometricInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verify(string $sessionId): VerifyFaceResponse;

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verifyIdentity(string $sessionId, Authenticatable $user): VerifyIdentityResponse;
}
