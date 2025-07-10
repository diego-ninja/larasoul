<?php

namespace Ninja\Larasoul\Api\Clients\Liveness;

use Ninja\Larasoul\Api\Clients\Client;
use Ninja\Larasoul\Api\Responses\EnrollAccountResponse;
use Ninja\Larasoul\Api\Contracts\BiometricInterface;
use Ninja\Larasoul\DTO\UserAccount;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

abstract class LivenessApiClient extends Client implements BiometricInterface
{
    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function enroll(string $sessionId, UserAccount $account): EnrollAccountResponse
    {
        $response = $this->call(
            endpoint: VerisoulApiEndpoint::Enroll,
            data: ['session_id' => $sessionId, 'account_id' => $account->id],
        );

        return EnrollAccountResponse::from($response);
    }
}
