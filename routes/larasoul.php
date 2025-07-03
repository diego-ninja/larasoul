<?php

use Illuminate\Support\Facades\Route;
use Ninja\Larasoul\Http\Controllers\LivenessController;

Route::group([
    'prefix' => 'api/larasoul/liveness',
    'as' => 'liveness::',
    'middleware' => config('larasoul.auth_middleware'),
], function (): void {
    Route::get('/{sessionType}/start', [LivenessController::class, 'start'])
        ->name('larasoul.liveness.start');

    Route::get('/{sessionType}/{sessionId}/verify', [LivenessController::class, 'verify'])
        ->name('larasoul.liveness.verify');

    Route::post('/{sessionType}/enroll', [LivenessController::class, 'enroll'])
        ->name('larasoul.liveness.enroll');
});
