<?php

namespace Ninja\Larasoul\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

final readonly class VerisoulSessionManager
{
    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Store Verisoul session ID
     */
    public function storeSessionId(
        string $sessionId,
        ?int $userId = null,
        array $metadata = [],
        ?int $ttl = null
    ): void {
        $ttl = $ttl ?? self::DEFAULT_TTL;

        $sessionData = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'metadata' => $metadata,
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addSeconds($ttl)->toISOString(),
        ];

        // Store in Laravel session
        Session::put(config('larasoul.session.verisoul_session_id'), $sessionData);

        // Store in cache if user is authenticated
        if ($userId) {
            Cache::put(
                $this->getCacheKey($userId),
                $sessionData,
                $ttl
            );
        }

        // Also store by session ID for lookups
        Cache::put(
            $this->getSessionCacheKey($sessionId),
            $sessionData,
            $ttl
        );
    }

    /**
     * Get session data by user ID
     */
    public function getSessionData(?int $userId = null): ?array
    {
        // Try to get from session first
        $sessionData = Session::get(config('larasoul.session.verisoul_session_id'));
        if ($sessionData && $this->isValidSessionData($sessionData)) {
            return $sessionData;
        }

        // Try to get from cache if user is provided
        if ($userId) {
            $sessionData = Cache::get($this->getCacheKey($userId));
            if ($sessionData && $this->isValidSessionData($sessionData)) {
                return $sessionData;
            }
        }

        return null;
    }

    /**
     * Get session data by Verisoul session ID
     */
    public function getSessionDataBySessionId(string $sessionId): ?array
    {
        $sessionData = Cache::get($this->getSessionCacheKey($sessionId));

        if ($sessionData && $this->isValidSessionData($sessionData)) {
            return $sessionData;
        }

        return null;
    }

    /**
     * Get current Verisoul session ID
     */
    public function getCurrentSessionId(?int $userId = null): ?string
    {
        $sessionData = $this->getSessionData($userId);

        return $sessionData['session_id'] ?? null;
    }

    /**
     * Clear session ID for user
     */
    public function clearSessionId(?int $userId = null): void
    {
        // Clear from Laravel session
        Session::forget(config('larasoul.session.verisoul_session_id'));

        // Clear from cache if user is provided
        if ($userId) {
            Cache::forget($this->getCacheKey($userId));
        }
    }

    /**
     * Clear session ID by session ID
     */
    public function clearSessionIdBySessionId(string $sessionId): void
    {
        $sessionData = $this->getSessionDataBySessionId($sessionId);

        if ($sessionData && isset($sessionData['user_id'])) {
            $this->clearSessionId($sessionData['user_id']);
        }

        Cache::forget($this->getSessionCacheKey($sessionId));
    }

    /**
     * Check if session ID exists
     */
    public function hasSessionId(?int $userId = null): bool
    {
        return $this->getCurrentSessionId($userId) !== null;
    }

    /**
     * Update session metadata
     */
    public function updateMetadata(?int $userId = null, array $metadata = []): bool
    {
        $sessionData = $this->getSessionData($userId);

        if (! $sessionData) {
            return false;
        }

        $sessionData['metadata'] = array_merge(
            $sessionData['metadata'] ?? [],
            $metadata
        );

        $this->storeSessionId(
            sessionId: $sessionData['session_id'],
            userId: $sessionData['user_id'],
            metadata: $sessionData['metadata']
        );

        return true;
    }

    /**
     * Get all active sessions (for admin purposes)
     */
    public function getActiveSessions(): array
    {
        // This would require a more complex implementation
        // For now, return empty array as this is mainly for debugging
        return [];
    }

    /**
     * Cleanup expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        // This would require a scheduled job in a real implementation
        // For now, return 0 as cache will handle TTL automatically
        return 0;
    }

    private function getCacheKey(int $userId): string
    {
        return sprintf(
            '%s_user_%s',
            config('larasoul.session.cache_key_prefix'),
            $userId
        );
    }

    private function getSessionCacheKey(string $sessionId): string
    {
        return sprintf(
            '%s_session_%s',
            config('larasoul.session.cache_key_prefix'),
            $sessionId
        );
    }

    private function isValidSessionData(array $sessionData): bool
    {
        if (empty($sessionData['session_id'])) {
            return false;
        }

        if (! isset($sessionData['expires_at'])) {
            return true; // Assume valid if no expiry set
        }

        try {
            $expiresAt = new \DateTime($sessionData['expires_at']);

            return $expiresAt > new \DateTime;
        } catch (\Exception) {
            return false;
        }
    }
}
