<?php

namespace Ninja\Larasoul\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\Api\Responses\EnrollAccountResponse;
use Ninja\Larasoul\Api\Responses\LivenessSessionResponse;
use Ninja\Larasoul\Enums\LivenessSession;

interface BiometricInterface
{
    public function session(LivenessSession $sessionType, ?string $referringSessionId = null): ?LivenessSessionResponse;

    public function enroll(string $sessionId, Authenticatable $user): EnrollAccountResponse;
}
