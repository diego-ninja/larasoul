<?php

namespace Ninja\Larasoul\Api\Contracts;

use Ninja\Larasoul\Api\Responses\EnrollAccountResponse;
use Ninja\Larasoul\Api\Responses\LivenessSessionResponse;
use Ninja\Larasoul\DTO\UserAccount;
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
    public function enroll(string $sessionId, UserAccount $account): EnrollAccountResponse;
}
