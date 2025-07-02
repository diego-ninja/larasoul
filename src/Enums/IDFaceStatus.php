<?php

namespace Ninja\Larasoul\Enums;

enum IDFaceStatus: string
{
    case NotAvailable = 'not_available';
    case LikeOriginal = 'likely_original_face';
    case Unconfirmed = 'cannot_confirm_id_is_authentic';
    case Unsupported = 'ocr_template_does_not_support_detection';
}
