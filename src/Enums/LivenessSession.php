<?php

namespace Ninja\Larasoul\Enums;

use Ninja\Larasoul\Api\Clients\Liveness\LivenessApiClient;
use Ninja\Larasoul\Facades\Verisoul;

enum LivenessSession: string
{
    case FaceMatch = 'face-match';
    case IDCheck = 'id-check';

    public function enabled(): bool
    {
        return match ($this) {
            self::FaceMatch => config('larasoul.verisoul.liveness.face_match.enabled'),
            self::IDCheck => config('larasoul.verisoul.liveness.id_check.enabled'),
        };
    }

    public function getVerificationType(): VerificationType
    {
        return match ($this) {
            self::FaceMatch => VerificationType::Face,
            self::IDCheck => VerificationType::Identity,
        };
    }

    public function api(): LivenessApiClient
    {
        return match ($this) {
            self::FaceMatch => Verisoul::faceMatch(),
            self::IDCheck => Verisoul::idCheck(),
        };
    }
}
