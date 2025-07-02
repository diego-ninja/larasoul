<?php

namespace Ninja\Larasoul\Enums;

enum IDStatus: string
{
    case FullDetected = 'full_id_detected';
    case NeedsRetry = 'could_not_confidently_determine_physical_id_user_needs_to_retry';
}
