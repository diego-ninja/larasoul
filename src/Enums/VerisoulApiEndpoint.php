<?php

namespace Ninja\Larasoul\Enums;

enum VerisoulApiEndpoint
{
    // Account endpoints
    case AccountGet;
    case AccountSessions;
    case AccountLinked;
    case AccountUpdate;
    case AccountDelete;

    // Session endpoints
    case SessionAuthenticate;
    case SessionUnauthenticated;
    case SessionGet;

    // Liveness endpoints
    case SessionStart;
    case Enroll;
    case VerifyFace;
    case VerifyIdentity;
    case VerifyId;

    // Phone endpoint
    case VerifyPhone;

    // Lists endpoints
    case ListCreate;
    case ListGetAll;
    case ListGet;
    case ListAddAccount;
    case ListDelete;
    case ListRemoveAccount;

    /**
     * Get endpoint path
     */
    public function url(): string
    {
        return match ($this) {
            self::AccountGet, self::AccountUpdate, self::AccountDelete => '/account/{account_id}',
            self::AccountSessions => '/account/{account_id}/sessions',
            self::AccountLinked => '/account/{account_id}/accounts-linked',
            self::SessionAuthenticate => '/session/authenticate',
            self::SessionUnauthenticated => '/session/unauthenticated',
            self::SessionGet => '/session/{session_id}',
            self::SessionStart => '/liveness/session',
            self::Enroll => '/liveness/enroll',
            self::VerifyFace => '/liveness/verify-face',
            self::VerifyIdentity => '/liveness/verify-identity',
            self::VerifyId => '/liveness/verify-id',
            self::VerifyPhone => '/phone',
            self::ListCreate, self::ListGet, self::ListDelete => '/list/{list_name}',
            self::ListGetAll => '/list',
            self::ListAddAccount, self::ListRemoveAccount => '/list/{list_name}/account/{account_id}',
        };
    }

    /**
     * Replace placeholders in endpoint path
     */
    public function withParameters(array $parameters = []): string
    {
        $endpoint = $this->url();

        foreach ($parameters as $key => $value) {
            $endpoint = str_replace('{'.$key.'}', $value, $endpoint);
        }

        return $endpoint;
    }

    /**
     * Get HTTP method for endpoint
     */
    public function getMethod(): string
    {
        return match ($this) {
            // GET endpoints
            self::AccountGet,
            self::AccountSessions,
            self::AccountLinked,
            self::SessionGet,
            self::SessionStart,
            self::ListGetAll,
            self::ListGet => 'GET',

            // POST endpoints
            self::SessionAuthenticate,
            self::SessionUnauthenticated,
            self::Enroll,
            self::VerifyFace,
            self::VerifyId,
            self::VerifyIdentity,
            self::VerifyPhone,
            self::ListCreate,
            self::ListAddAccount => 'POST',

            // PUT endpoints
            self::AccountUpdate => 'PUT',

            // DELETE endpoints
            self::AccountDelete,
            self::ListDelete,
            self::ListRemoveAccount => 'DELETE',
        };
    }
}
