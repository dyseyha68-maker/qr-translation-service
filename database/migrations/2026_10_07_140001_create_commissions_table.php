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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('commission_reference', 64)->unique()->index();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->foreignId('referral_attribution_id')->constrained('referral_attributions')->restrictOnDelete();
            $table->foreignId('referral_partner_id')->constrained('referral_partners')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('eligible_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 32)->default('earned')->index();
            $table->timestamp('earned_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['referral_partner_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
