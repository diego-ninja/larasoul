<?php

namespace Ninja\Larasoul\Enums;

enum VerificationType: string
{
    case Phone = 'phone';
    case Face = 'face';
    case Identity = 'identity';
}
