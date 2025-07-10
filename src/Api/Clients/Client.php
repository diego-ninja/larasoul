<?php

namespace Ninja\Larasoul\Api\Clients;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ninja\Larasoul\Api\Contracts\VerisoulApi;
use Ninja\Larasoul\Api\Support\CircuitBreaker;
use Ninja\Larasoul\Api\Support\RetryStrategy;
use Ninja\Larasoul\Enums\VerisoulApiEndpoint;
use Ninja\Larasoul\Enums\VerisoulEnvironment;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;
use Ninja\Larasoul\Exceptions\VerisoulValidationException;

abstract class Client implements VerisoulApi
{
    private string $apiKey;

    private VerisoulEnvironment $environment;

    private int $timeout;

    private int $connectTimeout;

    private RetryStrategy $retryStrategy;

    private CircuitBreaker $circuitBreaker;

    private array $headers;

    public function __construct(
        string $apiKey,
        VerisoulEnvironment $environment = VerisoulEnvironment::Sandbox,
        int $timeout = 30,
        int $connectTimeout = 10,
        int $retryAttempts = 3,
        int $retryDelay = 1000,
    ) {
        $this->validateConstructorParams($apiKey, $timeout, $connectTimeout);

        $this->apiKey = $apiKey;
        $this->environment = $environment;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;

        $this->retryStrategy = new RetryStrategy(
            maxAttempts: $retryAttempts,
            baseDelayMs: $retryDelay
        );

        $this->circuitBreaker = new CircuitBreaker(
            service: static::class,
            failureThreshold: 5,
            timeoutSeconds: $timeout,
            recoveryTime: 300
        );

        $this->headers = $this->buildDefaultHeaders();
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

        // $this->validateEndpointCall($endpoint, $parameters, $data);

        $operation = function () use ($method, $endpointPath, $data) {
            return $this->makeRequest($method, $endpointPath, $data);
        };

        return $this->circuitBreaker->call($operation);
    }

    /**
     * Make HTTP request with retry logic and error handling
     *
     * @throws VerisoulConnectionException|VerisoulApiException
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->getBaseUrl().$endpoint;
        $requestHeaders = array_merge($this->headers, $headers);
        $requestId = $this->generateRequestId();

        // Add request ID to headers for tracing
        $requestHeaders['X-Request-ID'] = $requestId;

        $this->logRequestStart($method, $endpoint, $data, $requestId);
        $startTime = microtime(true);

        try {
            // Execute with retry strategy
            $result = $this->retryStrategy->execute(function () use ($method, $url, $data, $requestHeaders, $requestId) {
                return $this->performHttpRequest($method, $url, $data, $requestHeaders, $requestId);
            });

            $duration = microtime(true) - $startTime;
            $this->logRequestSuccess($endpoint, $result, $duration, $requestId);
            $this->recordMetrics($endpoint, $method, 200, $duration, true);

            return $result;

        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            $this->logRequestError($endpoint, $e, $duration, $requestId);

            $statusCode = $e instanceof VerisoulApiException ? $e->statusCode : 0;
            $this->recordMetrics($endpoint, $method, $statusCode, $duration, false);

            throw $e;
        }
    }

    /**
     * Perform actual HTTP request with comprehensive error handling
     *
     * @throws VerisoulConnectionException
     * @throws VerisoulApiException
     */
    private function performHttpRequest(string $method, string $url, array $data, array $headers, string $requestId): array
    {
        try {
            $client = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry(1, 0) // Single retry at HTTP client level
                ->throw();

            $response = match (strtoupper($method)) {
                'GET' => $client->get($url, $data),
                'POST' => $client->post($url, $data),
                'PUT' => $client->put($url, $data),
                'DELETE' => $client->delete($url, $data),
                default => throw new InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            return $this->handleHttpResponse($response, $url, $requestId);

        } catch (ConnectionException $e) {
            throw VerisoulConnectionException::networkError($url, $e->getMessage());
        } catch (RequestException $e) {
            $response = $e->response;
            throw $this->createApiExceptionFromResponse($url, $response, $requestId);
        } catch (Exception $e) {
            throw VerisoulApiException::connectionFailed($url, $e);
        }
    }

    /**
     * Validate business logic in response (even if HTTP status is 200)
     *
     * @throws VerisoulApiException
     */
    private function validateBusinessLogicResponse(array $data, string $url): void
    {
        // Check for explicit error indicators
        if (isset($data['error'])) {
            $errorMessage = is_string($data['error']) ? $data['error'] : 'Unknown error';
            throw new VerisoulApiException(
                message: "Business logic error: {$errorMessage}",
                statusCode: 200,
                response: $data,
                endpoint: $url
            );
        }

        // Check for success flag being false
        if (isset($data['success']) && $data['success'] === false) {
            $message = $data['message'] ?? 'Operation failed';
            throw new VerisoulApiException(
                message: "Operation failed: {$message}",
                statusCode: 200,
                response: $data,
                endpoint: $url
            );
        }

        // Check for specific error status
        if (isset($data['status']) && $data['status'] === 'error') {
            $message = $data['message'] ?? $data['error_message'] ?? 'Unknown error';
            throw new VerisoulApiException(
                message: "API returned error status: {$message}",
                statusCode: 200,
                response: $data,
                endpoint: $url
            );
        }
    }

    /**
     * Handle HTTP response with validation and error checking
     *
     * @throws VerisoulApiException
     */
    private function handleHttpResponse(Response $response, string $url, string $requestId): array
    {
        if (! $response->successful()) {
            throw $this->createApiExceptionFromResponse($url, $response, $requestId);
        }

        // Validate response content type
        $contentType = $response->header('content-type');
        if (! str_contains($contentType, 'application/json')) {
            throw VerisoulApiException::invalidResponse(
                $url,
                "Expected JSON response, got: {$contentType}"
            );
        }

        // Parse and validate JSON
        try {
            $data = $response->json();
        } catch (Exception $e) {
            throw VerisoulApiException::invalidResponse(
                $url,
                "Invalid JSON response: {$e->getMessage()}"
            );
        }

        if (! is_array($data)) {
            throw VerisoulApiException::invalidResponse($url, 'Response is not a JSON object');
        }

        // Check for business-level errors in successful HTTP responses
        $this->validateBusinessLogicResponse($data, $url);

        return $data;
    }

    /**
     * Create appropriate API exception based on HTTP response
     */
    private function createApiExceptionFromResponse(string $url, Response $response, string $requestId): VerisoulApiException
    {
        $statusCode = $response->status();
        $responseData = $response->json() ?? [];

        // Add request ID to response data for tracing
        $responseData['request_id'] = $requestId;

        return match ($statusCode) {
            401 => VerisoulApiException::authenticationFailed($url),
            400 => VerisoulApiException::badRequest($url, $responseData),
            404 => new VerisoulApiException(
                message: 'Resource not found',
                statusCode: 404,
                response: $responseData,
                endpoint: $url
            ),
            422 => new VerisoulValidationException(
                message: $responseData['message'] ?? 'Validation failed',
                field: $responseData['field'] ?? 'unknown',
                value: $responseData['value'] ?? null
            ),
            429 => VerisoulApiException::rateLimitExceeded($url, $responseData),
            default => VerisoulApiException::serverError($url, $statusCode, $responseData),
        };
    }

    /**
     * Validate constructor parameters
     */
    private function validateConstructorParams(string $apiKey, int $timeout, int $connectTimeout): void
    {
        if (empty($apiKey)) {
            throw new InvalidArgumentException('API key is required');
        }

        if ($timeout <= 0 || $timeout > 300) {
            throw new InvalidArgumentException('Timeout must be between 1 and 300 seconds');
        }

        if ($connectTimeout <= 0 || $connectTimeout > $timeout) {
            throw new InvalidArgumentException('Connect timeout must be positive and <= timeout');
        }
    }

    /**
     * Validate endpoint call parameters
     *
     * @throws VerisoulValidationException
     */
    private function validateEndpointCall(VerisoulApiEndpoint $endpoint, array $parameters, array $data): void
    {
        // Validate required parameters are provided
        $url = $endpoint->url();
        preg_match_all('/\{(\w+)\}/', $url, $matches);
        $requiredParams = $matches[1] ?? [];

        foreach ($requiredParams as $param) {
            if (empty($parameters[$param])) {
                throw new VerisoulValidationException(
                    message: "Missing required parameter: {$param}",
                    field: $param,
                    value: null
                );
            }
        }

        // Validate data payload for POST/PUT requests
        $method = $endpoint->getMethod();
        if (in_array($method, ['POST', 'PUT']) && empty($data)) {
            Log::warning('Empty data payload for write operation', [
                'endpoint' => $endpoint->name,
                'method' => $method,
            ]);
        }
    }

    /**
     * Build default headers
     */
    private function buildDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => $this->apiKey,
            'User-Agent' => 'Larasoul/1.0 (Laravel PHP SDK)',
            'X-Client-Version' => '1.0.0',
        ];
    }

    /**
     * Generate unique request ID for tracing
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Log request start
     */
    private function logRequestStart(string $method, string $endpoint, array $data, string $requestId): void
    {
        Log::info('Verisoul API request started', [
            'request_id' => $requestId,
            'method' => $method,
            'endpoint' => $endpoint,
            'environment' => $this->environment->value,
            'data_keys' => array_keys($data),
            'base_url' => $this->getBaseUrl(),
        ]);
    }

    /**
     * Log successful request
     */
    private function logRequestSuccess(string $endpoint, array $response, float $duration, string $requestId): void
    {
        Log::info('Verisoul API request completed', [
            'request_id' => $requestId,
            'endpoint' => $endpoint,
            'duration_ms' => round($duration * 1000, 2),
            'response_keys' => array_keys($response),
            'success' => true,
        ]);
    }

    /**
     * Log request error
     */
    private function logRequestError(string $endpoint, Exception $exception, float $duration, string $requestId): void
    {
        $logData = [
            'request_id' => $requestId,
            'endpoint' => $endpoint,
            'duration_ms' => round($duration * 1000, 2),
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'success' => false,
        ];

        if ($exception instanceof VerisoulApiException) {
            $logData['status_code'] = $exception->statusCode;
            $logData['api_response'] = $exception->response;
        }

        Log::error('Verisoul API request failed', $logData);
    }

    /**
     * Record metrics for monitoring
     */
    private function recordMetrics(string $endpoint, string $method, int $statusCode, float $duration, bool $success): void
    {
        // This could integrate with your monitoring solution (Prometheus, etc.)
        $metrics = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'success' => $success,
            'environment' => $this->environment->value,
            'timestamp' => now()->toISOString(),
        ];

        // Log metrics for now, but this could be sent to a metrics collector
        Log::channel('metrics')->info('verisoul_api_call', $metrics);
    }
}
