<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', \Ninja\Larasoul\Enums\RiskStatus::values())->default('pending')->index();
            $table->enum('decision', \Ninja\Larasoul\Enums\VerisoulDecision::values())->nullable()->index();
            $table->decimal('score', 3)->nullable()->index(); // 0.00 to 1.00
            $table->json('signals')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('face_verified_at')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_risk_check_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['score', 'decision']);
            $table->index(['verified_at', 'expires_at']);
            $table->index('last_risk_check_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verisoul_profile');
    }
};
