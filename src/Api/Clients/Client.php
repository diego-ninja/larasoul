<?php

namespace Ninja\Larasoul\Api\Clients;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ninja\Larasoul\Contracts\VerisoulApi;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Enums\VerisoulEnvironment;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

abstract class Client implements VerisoulApi
{
    private string $apiKey;
    private VerisoulEnvironment $environment;
    private int $timeout;
    private int $retryAttempts;
    private int $retryDelay;

    public function __construct(
        string $apiKey,
        VerisoulEnvironment $environment = VerisoulEnvironment::Sandbox,
        int $timeout = 30,
        int $retryAttempts = 3,
        int $retryDelay = 1000,
    ) {
        $this->apiKey = $apiKey;
        $this->environment = $environment;
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
        $this->retryDelay = $retryDelay;
    }

    public static function create(string $apiKey, VerisoulEnvironment $environment = VerisoulEnvironment::Sandbox): Client
    {
        return new static($apiKey, $environment);
    }

    /**
     * @throws VerisoulApiException
     * @throws VerisoulConnectionException
     */
    public function get(string $endpoint, array $query = [], array $headers = []): array
    {
        return $this->makeRequest('GET', $endpoint, $query, $headers);
    }

    /**
     * @throws VerisoulConnectionException
     * @throws VerisoulApiException
     */
    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeRequest('POST', $endpoint, $data, $headers);
    }

    /**
     * @throws VerisoulConnectionException
     * @throws VerisoulApiException
     */
    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeRequest('PUT', $endpoint, $data, $headers);
    }

    /**
     * @throws VerisoulConnectionException
     * @throws VerisoulApiException
     */
    public function delete(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeRequest('DELETE', $endpoint, $data, $headers);
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function setEnvironment(VerisoulEnvironment $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    public function getEnvironment(): VerisoulEnvironment
    {
        return $this->environment;
    }

    public function getBaseUrl(): string
    {
        return $this->environment->getBaseUrl();
    }

    /**
     * @throws VerisoulConnectionException
     * @throws VerisoulApiException
     */
    protected function call(VerisoulApiEndpoint $endpoint, array $parameters = [], array $data = []): array
    {
        $endpointPath = $endpoint->withParameters($parameters);
        $method = $endpoint->getMethod();

        return match (mb_strtoupper($method)) {
            'GET' => $this->get($endpointPath, $data),
            'POST' => $this->post($endpointPath, $data),
            'PUT' => $this->put($endpointPath, $data),
            'DELETE' => $this->delete($endpointPath, $data),
            default => throw new VerisoulApiException("Unsupported HTTP method: {$method}"),
        };
    }
    /**
     * Make HTTP request with retry logic and error handling
     * @throws VerisoulConnectionException|VerisoulApiException
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->getBaseUrl() . $endpoint;
        $headers = $this->buildHeaders($headers);

        $this->logRequest($method, $endpoint, $data);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryAttempts) {
            try {
                $response = $this->performRequest($method, $url, $data, $headers);
                $responseData = $this->handleResponse($response, $endpoint);

                $this->logResponse($endpoint, $responseData, $response->status());

                return $responseData;

            } catch (ConnectionException $e) {
                $lastException = VerisoulConnectionException::networkError($endpoint, $e->getMessage());
                $this->logError($endpoint, $lastException, $attempt + 1);

                if ($attempt === $this->retryAttempts - 1) {
                    throw $lastException;
                }

                $this->sleep();
                $attempt++;
                continue;

            } catch (RequestException $e) {
                $response = $e->response;
                $lastException = $this->createApiException($endpoint, $response);
                $this->logError($endpoint, $lastException, $attempt + 1);

                // Don't retry on 4xx errors except 429 (rate limit)
                if ($response->status() >= 400 && $response->status() < 500 && 429 !== $response->status()) {
                    throw $lastException;
                }

                if ($attempt === $this->retryAttempts - 1) {
                    throw $lastException;
                }

                $this->sleep();
                $attempt++;
                continue;

            } catch (Exception $e) {
                $lastException = VerisoulApiException::connectionFailed($endpoint, $e);
                $this->logError($endpoint, $lastException, $attempt + 1);

                if ($attempt === $this->retryAttempts - 1) {
                    throw $lastException;
                }

                $this->sleep();
                $attempt++;
                continue;
            }
        }

        throw $lastException ?? VerisoulApiException::connectionFailed($endpoint, new Exception('Unknown error'));
    }

    /**
     * Perform the actual HTTP request
     * @throws ConnectionException
     */
    private function performRequest(string $method, string $url, array $data, array $headers): Response
    {
        $client = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->throw();

        return match (mb_strtoupper($method)) {
            'GET' => $client->get($url, $data),
            'POST' => $client->post($url, $data),
            'PUT' => $client->put($url, $data),
            'DELETE' => $client->delete($url, $data),
            default => throw new InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Handle HTTP response and validate
     * @throws VerisoulApiException
     */
    private function handleResponse(Response $response, string $endpoint): array
    {
        if ( ! $response->successful()) {
            throw $this->createApiException($endpoint, $response);
        }

        $data = $response->json();

        if ( ! is_array($data)) {
            throw VerisoulApiException::invalidResponse($endpoint, 'Response is not valid JSON');
        }

        return $data;
    }

    /**
     * Create appropriate API exception based on response
     */
    private function createApiException(string $endpoint, Response $response): VerisoulApiException
    {
        $statusCode = $response->status();
        $responseData = $response->json() ?? [];

        return match ($statusCode) {
            401 => VerisoulApiException::authenticationFailed($endpoint),
            400 => VerisoulApiException::badRequest($endpoint, $responseData),
            429 => VerisoulApiException::rateLimitExceeded($endpoint, $responseData),
            default => VerisoulApiException::serverError($endpoint, $statusCode, $responseData),
        };
    }

    /**
     * Build request headers with authentication
     */
    private function buildHeaders(array $customHeaders = []): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => $this->apiKey,
            'User-Agent' => 'RC-Customers-API/1.0',
        ];

        return array_merge($headers, $customHeaders);
    }

    /**
     * Sleep between retry attempts
     */
    private function sleep(): void
    {
        usleep($this->retryDelay * 1000);
    }

    /**
     * Log request details
     */
    private function logRequest(string $method, string $endpoint, array $data): void
    {
        Log::info('Verisoul API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'environment' => $this->environment->value,
            'data_keys' => array_keys($data),
            'base_url' => $this->getBaseUrl(),
        ]);
    }

    /**
     * Log response details
     */
    private function logResponse(string $endpoint, array $data, int $statusCode): void
    {
        Log::info('Verisoul API response', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response_keys' => array_keys($data),
            'environment' => $this->environment->value,
        ]);
    }

    /**
     * Log error details
     */
    private function logError(string $endpoint, VerisoulApiException $exception, int $attempt): void
    {
        Log::warning('Verisoul API error', [
            'endpoint' => $endpoint,
            'attempt' => $attempt,
            'max_attempts' => $this->retryAttempts,
            'error' => $exception->getErrorDetails(),
            'environment' => $this->environment->value,
        ]);
    }
}
