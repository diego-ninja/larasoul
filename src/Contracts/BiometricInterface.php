<?php

namespace Ninja\Larasoul\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\Api\Responses\EnrollAccountResponse;
use Ninja\Larasoul\Api\Responses\LivenessSessionResponse;

interface BiometricInterface
{
    public function session(?string $referringSessionId = null): ?LivenessSessionResponse;

    public function enroll(string $sessionId, Authenticatable $user): EnrollAccountResponse;
}
