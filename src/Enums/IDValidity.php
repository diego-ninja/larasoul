<?php

namespace Ninja\Larasoul\Enums;

enum IDValidity: string
{
    case LikeAuthentic = 'likely_authentic_id';
    case Unconfirmed = 'cannot_confirm_id_is_authentic';
    case Fake = 'likely_fake_id';
}
