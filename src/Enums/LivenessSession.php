<?php

namespace Ninja\Larasoul\Enums;

enum LivenessSession: string
{
    case FaceMatch = 'face-match';
    case IDCheck = 'id-check';

    public function enabled(): bool
    {
        return match ($this) {
            self::FaceMatch => config('services.verisoul.face_match.enabled')  && config('services.verisoul.enabled'),
            self::IDCheck => config('services.verisoul.id_check.enabled') && config('services.verisoul.enabled'),
        };
    }
}
