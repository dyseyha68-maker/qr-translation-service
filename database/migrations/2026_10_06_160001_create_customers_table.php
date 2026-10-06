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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')
                ->nullable()
                ->constrained('telegram_users')
                ->restrictOnDelete();
            $table->string('name', 255)->index();
            $table->string('phone', 50)->nullable()->index();
            $table->string('email', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('ACTIVE')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
