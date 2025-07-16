<?php

namespace Ninja\Larasoul\Services;

use InvalidArgumentException;
use Ninja\Larasoul\Api\Clients\AccountClient;
use Ninja\Larasoul\Api\Clients\ListClient;
use Ninja\Larasoul\Api\Clients\Liveness\FaceMatchClient;
use Ninja\Larasoul\Api\Clients\Liveness\IDCheckClient;
use Ninja\Larasoul\Api\Clients\PhoneClient;
use Ninja\Larasoul\Api\Clients\SessionClient;
use Ninja\Larasoul\Api\Contracts\AccountInterface;
use Ninja\Larasoul\Api\Contracts\FaceMatchInterface;
use Ninja\Larasoul\Api\Contracts\IDCheckInterface;
use Ninja\Larasoul\Api\Contracts\ListInterface;
use Ninja\Larasoul\Api\Contracts\PhoneInterface;
use Ninja\Larasoul\Api\Contracts\SessionInterface;
use Ninja\Larasoul\Enums\VerisoulEnvironment;

class VerisoulApi
{
    private array $clients = [];

    public function __construct(
        private readonly string $apiKey,
        private readonly VerisoulEnvironment $environment,
        private readonly array $config = []
    ) {
        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('Verisoul API key is required');
        }
    }

    public function account(): AccountInterface
    {
        return $this->clients['account'] ??= new AccountClient(
            $this->apiKey,
            $this->environment,
            $this->config['timeout'] ?? 30,
            $this->config['connect_timeout'] ?? 10,
            $this->config['retry_attempts'] ?? 3,
            $this->config['retry_delay'] ?? 1000
        );
    }

    public function session(): SessionInterface
    {
        return $this->clients['session'] ??= new SessionClient(
            $this->apiKey,
            $this->environment,
            $this->config['timeout'] ?? 30,
            $this->config['connect_timeout'] ?? 10,
            $this->config['retry_attempts'] ?? 3,
            $this->config['retry_delay'] ?? 1000
        );
    }

    public function phone(): PhoneInterface
    {
        return $this->clients['phone'] ??= new PhoneClient(
            $this->apiKey,
            $this->environment,
            $this->config['timeout'] ?? 30,
            $this->config['connect_timeout'] ?? 10,
            $this->config['retry_attempts'] ?? 3,
            $this->config['retry_delay'] ?? 1000
        );
    }

    public function list(): ListInterface
    {
        return $this->clients['list'] ??= new ListClient(
            $this->apiKey,
            $this->environment,
            $this->config['timeout'] ?? 30,
            $this->config['connect_timeout'] ?? 10,
            $this->config['retry_attempts'] ?? 3,
            $this->config['retry_delay'] ?? 1000
        );
    }

    public function faceMatch(): FaceMatchInterface
    {
        return $this->clients['faceMatch'] ??= new FaceMatchClient(
            $this->apiKey,
            $this->environment,
            $this->config['timeout'] ?? 30,
            $this->config['connect_timeout'] ?? 10,
            $this->config['retry_attempts'] ?? 3,
            $this->config['retry_delay'] ?? 1000
        );
    }

    public function idCheck(): IDCheckInterface
    {
        return $this->clients['idCheck'] ??= new IDCheckClient(
            $this->apiKey,
            $this->environment,
            $this->config['timeout'] ?? 30,
            $this->config['connect_timeout'] ?? 10,
            $this->config['retry_attempts'] ?? 3,
            $this->config['retry_delay'] ?? 1000
        );
    }

    /**
     * Get configuration for specific service
     */
    public function getConfig(string $service): array
    {
        return $this->config[$service] ?? [];
    }

    /**
     * Check if a service is enabled
     */
    public function isEnabled(string $service): bool
    {
        return $this->config[$service]['enabled'] ?? false;
    }

    /**
     * Get current environment
     */
    public function getEnvironment(): VerisoulEnvironment
    {
        return $this->environment;
    }

    /**
     * Check if running in sandbox mode
     */
    public function isSandbox(): bool
    {
        return $this->environment === VerisoulEnvironment::Sandbox;
    }
}
