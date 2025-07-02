<?php

namespace Ninja\Larasoul\Contracts;

use Ninja\Larasoul\Api\Responses\VerifyPhoneResponse;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

interface PhoneInterface
{
    /**
     * Verify a phone number and return carrier and line type information
     *
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verifyPhone(string $phoneNumber): VerifyPhoneResponse;
}