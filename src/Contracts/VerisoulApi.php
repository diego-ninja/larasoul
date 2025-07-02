<?php

namespace Ninja\Larasoul\Contracts;

use Ninja\Larasoul\Enums\VerisoulEnvironment;

interface VerisoulApi
{
    /**
     * Perform GET request to Verisoul API
     */
    public function get(string $endpoint, array $query = [], array $headers = []): array;

    /**
     * Perform POST request to Verisoul API
     */
    public function post(string $endpoint, array $data = [], array $headers = []): array;

    /**
     * Set API key for authentication
     */
    public function setApiKey(string $apiKey): self;

    /**
     * Set environment (sandbox/production)
     */
    public function setEnvironment(VerisoulEnvironment $environment): self;

    /**
     * Get current environment
     */
    public function getEnvironment(): VerisoulEnvironment;

    /**
     * Get current base URL based on environment
     */
    public function getBaseUrl(): string;

}