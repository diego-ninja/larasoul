<?php

namespace Ninja\Larasoul\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\Api\Responses\EnrollAccountResponse;
use Ninja\Larasoul\Api\Responses\LivenessSessionResponse;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

interface BiometricInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function session(?string $referringSessionId = null): ?LivenessSessionResponse;

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function enroll(string $sessionId, Authenticatable $user): EnrollAccountResponse;
}
