<?php

namespace Ninja\Larasoul\Contracts;

use Ninja\Larasoul\Api\Responses\VerifyIdResponse;

interface IDCheckInterface extends BiometricInterface
{
    public function verifyId(string $sessionId): VerifyIdResponse;
}
