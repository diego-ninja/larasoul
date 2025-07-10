<?php

namespace Ninja\Larasoul\Api\Clients;

use Ninja\Larasoul\Api\Contracts\PhoneInterface;
use Ninja\Larasoul\Api\Responses\VerifyPhoneResponse;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

final class PhoneClient extends Client implements PhoneInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function verifyPhone(string $phoneNumber): VerifyPhoneResponse
    {
        $response = $this->call(
            VerisoulApiEndpoint::VerifyPhone,
            [],
            ['phone_number' => $phoneNumber]
        );

        return VerifyPhoneResponse::from($response);
    }
}
