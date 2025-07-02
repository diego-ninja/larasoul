<?php

namespace Ninja\Larasoul\Enums;

enum IDBarcodeStatus: string
{
    case NotSpecifiedByTemplate = 'no_barcode_specified_by_template';
    case NotFound = 'barcode_requested_but_not_found';
    case ErrorReading = 'barcode_requested_but_error_reading';
    case NotParseable = 'barcode_requested_and_read_but_could_not_parse';
    case Success = 'success';
}
