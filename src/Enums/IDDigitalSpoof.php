<?php

namespace Ninja\Larasoul\Enums;

enum IDDigitalSpoof: string
{
    case LikelyPhysical = 'likely_physical_id';
    case NeedsRetry = 'could_not_confidently_determine_physical_id_user_needs_to_retry';
    case Unknown = 'unknown';
}
