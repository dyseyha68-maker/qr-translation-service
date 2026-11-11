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
        Schema::create('referral_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_event_id')
                ->constrained('referral_events')
                ->restrictOnDelete();
            $table->foreignId('referral_code_id')
                ->constrained('referral_codes')
                ->restrictOnDelete();
            $table->foreignId('telegram_user_id')
                ->nullable()
                ->constrained('telegram_users')
                ->restrictOnDelete();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();
            $table->foreignId('lead_id')
                ->nullable()
                ->constrained('leads')
                ->restrictOnDelete();
            $table->foreignId('referral_partner_id')
                ->constrained('referral_partners')
                ->restrictOnDelete();
            $table->foreignId('driver_id')
                ->constrained('drivers')
                ->restrictOnDelete();
            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->restrictOnDelete();
            $table->string('attribution_status', 32)->default('ACTIVE')->index();
            $table->timestamp('attributed_at')->index();
            $table->timestamp('changed_at')->nullable();
            $table->text('change_reason')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'attribution_status']);
            $table->index(['customer_id', 'attribution_status']);
            $table->index(['referral_partner_id', 'driver_id', 'vehicle_id'], 'ref_attr_partner_driver_veh_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_attributions');
    }
};
