<?php

namespace Ninja\Larasoul\Enums;

enum IDTextStatus: string
{
    case Unavailable = 'not_available';
    case LikelyOriginal = 'likely_original_text';
    case Unconfirmed = 'cannot_confirm_id_is_authentic';
}
