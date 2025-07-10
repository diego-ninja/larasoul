<?php

use Illuminate\Support\Facades\Route;
use Ninja\Larasoul\Http\Controllers\LivenessController;
use Ninja\Larasoul\Http\Controllers\VerisoulSessionController;

Route::group([
    'prefix' => 'larasoul/liveness',
    'as' => 'liveness::',
    'middleware' => config('larasoul.auth_middleware'),
], function (): void {
    Route::get('/{sessionType}/start', [LivenessController::class, 'start'])
        ->name('larasoul.liveness.start')
        ->where('sessionType', 'face-match|id-check');

    Route::get('/{sessionType}/verify', [LivenessController::class, 'verify'])
        ->name('larasoul.liveness.verify')
        ->where('sessionType', 'face-match|id-check');

    Route::post('/{sessionType}/enroll', [LivenessController::class, 'enroll'])
        ->name('larasoul.liveness.enroll')
        ->where('sessionType', 'face-match|id-check');
});

// Verisoul Session Management Routes
Route::group([
    'prefix' => 'api/larasoul',
    'as' => 'session::',
], function (): void {
    Route::post('/session', [VerisoulSessionController::class, 'store'])
        ->name('session.store');

    Route::get('/session', [VerisoulSessionController::class, 'show'])
        ->name('session.show')
        ->middleware(config('larasoul.auth_middleware'));

    Route::delete('/session', [VerisoulSessionController::class, 'destroy'])
        ->name('session.destroy')
        ->middleware(config('larasoul.auth_middleware'));
});
