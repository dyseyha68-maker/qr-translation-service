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
        Schema::create('referral_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('type', 50)->index();
            $table->string('phone', 50)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('ACTIVE')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_partners');
    }
};
