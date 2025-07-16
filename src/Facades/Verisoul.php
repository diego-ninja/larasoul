<?php

namespace Ninja\Larasoul\Facades;

use Illuminate\Support\Facades\Facade;
use Ninja\Larasoul\Api\Contracts\AccountInterface;
use Ninja\Larasoul\Api\Contracts\FaceMatchInterface;
use Ninja\Larasoul\Api\Contracts\IDCheckInterface;
use Ninja\Larasoul\Api\Contracts\ListInterface;
use Ninja\Larasoul\Api\Contracts\PhoneInterface;
use Ninja\Larasoul\Api\Contracts\SessionInterface;
use Ninja\Larasoul\Enums\VerisoulEnvironment;
use Ninja\Larasoul\Services\VerisoulApi;

/**
 * @method static AccountInterface account()
 * @method static SessionInterface session()
 * @method static PhoneInterface phone()
 * @method static ListInterface list()
 * @method static FaceMatchInterface faceMatch()
 * @method static IDCheckInterface idCheck()
 * @method static array getConfig(string $service)
 * @method static bool isEnabled(string $service)
 * @method static VerisoulEnvironment getEnvironment()
 * @method static bool isSandbox()
 */
class Verisoul extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VerisoulApi::class;
    }
}
