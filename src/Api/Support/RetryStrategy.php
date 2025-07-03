<?php

namespace Ninja\Larasoul\Api\Support;

use Exception;
use Illuminate\Support\Facades\Log;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;

final readonly class RetryStrategy
{
    public function __construct(
        private int $maxAttempts = 3,
        private int $baseDelayMs = 1000,
        private float $backoffMultiplier = 2.0,
        private int $maxDelayMs = 30000
    ) {}

    /**
     * @throws Exception
     */
    public function execute(callable $callback)
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $this->maxAttempts) {
            try {
                return $callback();
            } catch (VerisoulApiException $e) {
                $lastException = $e;

                // Don't retry on certain errors
                if (! $this->shouldRetry($e, $attempt)) {
                    throw $e;
                }

                if ($attempt < $this->maxAttempts) {
                    $delay = $this->calculateDelay($attempt);
                    Log::info('Retrying operation', [
                        'attempt' => $attempt,
                        'delay_ms' => $delay,
                        'error' => $e->getMessage(),
                    ]);

                    usleep($delay * 1000);
                }

                $attempt++;
            }
        }

        throw $lastException;
    }

    private function shouldRetry(Exception $e, int $attempt): bool
    {
        // Don't retry validation errors
        if ($e instanceof VerisoulValidationException) {
            return false;
        }

        // Don't retry 4xx errors except 429 (rate limit) and 408 (timeout)
        if ($e instanceof VerisoulApiException) {
            $status = $e->statusCode;
            if ($status >= 400 && $status < 500 && ! in_array($status, [408, 429])) {
                return false;
            }
        }

        // Retry connection errors and 5xx errors
        return $e instanceof VerisoulConnectionException ||
            ($e instanceof VerisoulApiException && $e->statusCode >= 500) ||
            $attempt < $this->maxAttempts;
    }

    private function calculateDelay(int $attempt): int
    {
        $delay = $this->baseDelayMs * pow($this->backoffMultiplier, $attempt - 1);

        // Add jitter (random factor) to prevent thundering herd
        $jitter = mt_rand(0, (int) ($delay * 0.1));
        $delay += $jitter;

        return min($delay, $this->maxDelayMs);
    }
}
