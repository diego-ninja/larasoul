<?php

namespace Ninja\Larasoul\Facades;

use Illuminate\Support\Facades\Facade;
use Ninja\Larasoul\Api\Contracts\PhoneInterface;
use Ninja\Larasoul\Api\Contracts\SessionInterface;
use Ninja\Larasoul\Contracts\AccountInterface;
use Ninja\Larasoul\Contracts\FaceMatchInterface;
use Ninja\Larasoul\Contracts\IDCheckInterface;
use Ninja\Larasoul\Contracts\ListInterface;
use Ninja\Larasoul\Enums\VerisoulEnvironment;
use Ninja\Larasoul\Services\VerisoulManager;

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
        return VerisoulManager::class;
    }
}
