<?php

namespace Ninja\Larasoul\Enums;

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
}
