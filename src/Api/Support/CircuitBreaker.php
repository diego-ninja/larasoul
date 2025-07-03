<?php

namespace Ninja\Larasoul\Api\Support;

use Exception;
use Illuminate\Support\Facades\Cache;
use Ninja\Larasoul\Exceptions\VerisoulApiException;

final readonly class CircuitBreaker
{
    private const string STATE_CLOSED = 'closed';

    private const string STATE_OPEN = 'open';

    private const string STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private string $service,
        private int $failureThreshold = 5,
        private int $timeoutSeconds = 60,
        private int $recoveryTime = 300 // 5 minutes
    ) {}

    /**
     * @throws VerisoulApiException
     */
    public function call(callable $callback)
    {
        $state = $this->getState();

        switch ($state) {
            case self::STATE_OPEN:
                if ($this->shouldAttemptRecovery()) {
                    $this->setState(self::STATE_HALF_OPEN);

                    return $this->executeCall($callback);
                }
                throw new VerisoulApiException(
                    message: "Circuit breaker is OPEN for service: {$this->service}",
                    statusCode: 503
                );

            case self::STATE_HALF_OPEN:
                try {
                    $result = $this->executeCall($callback);
                    $this->setState(self::STATE_CLOSED);
                    $this->resetFailureCount();

                    return $result;
                } catch (Exception $e) {
                    $this->setState(self::STATE_OPEN);
                    $this->recordFailure();
                    throw $e;
                }

            case self::STATE_CLOSED:
            default:
                try {
                    return $this->executeCall($callback);
                } catch (Exception $e) {
                    $this->recordFailure();
                    if ($this->getFailureCount() >= $this->failureThreshold) {
                        $this->setState(self::STATE_OPEN);
                    }
                    throw $e;
                }
        }
    }

    /**
     * @throws VerisoulApiException
     */
    private function executeCall(callable $callback)
    {
        $startTime = microtime(true);

        try {
            $result = $callback();
            $this->recordSuccess();

            return $result;
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;

            // Consider timeout as failure
            if ($duration >= $this->timeoutSeconds) {
                throw new VerisoulApiException(
                    message: "Operation timed out after {$duration} seconds",
                    statusCode: 504,
                    previous: $e
                );
            }

            throw $e;
        }
    }

    private function getState(): string
    {
        return Cache::get($this->getStateKey(), self::STATE_CLOSED);
    }

    private function setState(string $state): void
    {
        Cache::put($this->getStateKey(), $state, now()->addSeconds($this->recoveryTime));
    }

    private function getFailureCount(): int
    {
        return Cache::get($this->getFailureCountKey(), 0);
    }

    private function recordFailure(): void
    {
        $count = $this->getFailureCount() + 1;
        Cache::put($this->getFailureCountKey(), $count, now()->addMinutes(10));
    }

    private function recordSuccess(): void
    {
        // Optionally reduce failure count on success
        $count = max(0, $this->getFailureCount() - 1);
        if ($count > 0) {
            Cache::put($this->getFailureCountKey(), $count, now()->addMinutes(10));
        } else {
            Cache::forget($this->getFailureCountKey());
        }
    }

    private function resetFailureCount(): void
    {
        Cache::forget($this->getFailureCountKey());
    }

    private function shouldAttemptRecovery(): bool
    {
        $lastFailure = Cache::get($this->getLastFailureKey());

        return $lastFailure === null ||
            (time() - $lastFailure) >= $this->recoveryTime;
    }

    private function getStateKey(): string
    {
        return "circuit_breaker:{$this->service}:state";
    }

    private function getFailureCountKey(): string
    {
        return "circuit_breaker:{$this->service}:failures";
    }

    private function getLastFailureKey(): string
    {
        return "circuit_breaker:{$this->service}:last_failure";
    }
}
