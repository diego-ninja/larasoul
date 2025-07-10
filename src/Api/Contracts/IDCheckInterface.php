<?php

namespace Ninja\Larasoul\Api\Contracts;

use Ninja\Larasoul\Api\Responses\VerifyIdResponse;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

interface IDCheckInterface extends BiometricInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verify(string $sessionId): VerifyIdResponse;
}
