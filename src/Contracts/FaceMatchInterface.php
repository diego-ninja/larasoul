<?php

namespace Ninja\Larasoul\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\Api\Responses\VerifyFaceResponse;
use Ninja\Larasoul\Api\Responses\VerifyIdentityResponse;

interface FaceMatchInterface extends BiometricInterface
{
    public function verifyFace(string $sessionId): VerifyFaceResponse;
    public function verifyIdentity(string $sessionId, Authenticatable $user): VerifyIdentityResponse;

}