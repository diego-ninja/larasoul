<?php

namespace Ninja\Larasoul\Http\Helpers;

use Illuminate\Support\Facades\Route;

/**
 * Route helper for applying verification middleware
 */
class VerificationRoutes
{
    /**
     * Apply basic verification to routes
     */
    public static function verified(\Closure $callback): void
    {
        Route::middleware(['auth', 'verified'])->group($callback);
    }

    /**
     * Apply low risk requirement to routes
     */
    public static function lowRisk(\Closure $callback): void
    {
        Route::middleware(['auth', 'low-risk'])->group($callback);
    }

    /**
     * Apply premium verification to routes
     */
    public static function premium(\Closure $callback): void
    {
        Route::middleware(['auth', 'premium-verified'])->group($callback);
    }

    /**
     * Apply high security verification to routes
     */
    public static function highSecurity(\Closure $callback): void
    {
        Route::middleware(['auth', 'high-security'])->group($callback);
    }

    /**
     * Apply document verification requirement
     */
    public static function requireDocument(\Closure $callback): void
    {
        Route::middleware(['auth', 'require.document'])->group($callback);
    }

    /**
     * Apply face verification requirement
     */
    public static function requireFace(\Closure $callback): void
    {
        Route::middleware(['auth', 'require.face'])->group($callback);
    }

    /**
     * Apply phone verification requirement
     */
    public static function requirePhone(\Closure $callback): void
    {
        Route::middleware(['auth', 'require.phone'])->group($callback);
    }

    /**
     * Apply custom risk level requirement
     */
    public static function requireRiskLevel(string $maxLevel, \Closure $callback): void
    {
        Route::middleware(['auth', "require.risk.level:{$maxLevel}"])->group($callback);
    }

    /**
     * Apply custom verification level requirement
     */
    public static function requireLevel(string $level, \Closure $callback): void
    {
        Route::middleware(['auth', "require.verification.level:{$level}"])->group($callback);
    }
}
