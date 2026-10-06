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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();
            $table->foreignId('telegram_user_id')
                ->nullable()
                ->constrained('telegram_users')
                ->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('service_type', 100)->nullable();
            $table->string('status', 32)->default('NEW')->index();
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('source', 32)->default('TELEGRAM_REFERRAL')->index();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['telegram_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
