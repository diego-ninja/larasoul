<?php

use Illuminate\Support\Facades\Route;
use Ninja\Larasoul\Enums\LivenessSession;
use Ninja\Larasoul\Http\Controllers\Api\LivenessController;
use Ninja\Larasoul\Http\Controllers\Api\PhoneController;
use Ninja\Larasoul\Http\Controllers\Api\SessionController;

Route::group([
    'prefix' => 'api/larasoul',
    'middleware' => config('larasoul.auth_middleware'),
], function (): void {
    Route::group([
        'prefix' => 'liveness',
    ], function (): void {
        Route::get('/{sessionType}/start', [LivenessController::class, 'start'])
            ->where('sessionType', implode('|', array_map(fn ($type) => $type->value, LivenessSession::cases())))
            ->name('larasoul.liveness.start');

        Route::get('/{sessionType}/verify', [LivenessController::class, 'verify'])
            ->where('sessionType', implode('|', array_map(fn ($type) => $type->value, LivenessSession::cases())))
            ->name('larasoul.liveness.verify');

        Route::post('/{sessionType}/enroll', [LivenessController::class, 'enroll'])
            ->where('sessionType', implode('|', array_map(fn ($type) => $type->value, LivenessSession::cases())))
            ->name('larasoul.liveness.enroll');
    });

    Route::group([
        'prefix' => 'phone',
    ], function (): void {
        Route::post('/verify', [PhoneController::class, 'verify'])
            ->name('larasoul.phone.verify');
    });
});

Route::post('/api/larasoul/session', [SessionController::class, 'store'])
    ->name('larasoul.session.store');
