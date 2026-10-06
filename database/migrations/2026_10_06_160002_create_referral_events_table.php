<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')
                ->nullable()
                ->constrained('referral_codes')
                ->restrictOnDelete();
            $table->foreignId('telegram_user_id')
                ->nullable()
                ->constrained('telegram_users')
                ->restrictOnDelete();
            $table->string('event_type', 32)->index();
            $table->string('event_token', 64)->nullable()->index();
            $table->unsignedBigInteger('telegram_update_id')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['referral_code_id', 'telegram_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_events');
    }
};
