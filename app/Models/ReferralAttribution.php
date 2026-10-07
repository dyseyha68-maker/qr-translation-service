<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReferralAttributionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralAttribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_event_id',
        'referral_code_id',
        'telegram_user_id',
        'customer_id',
        'lead_id',
        'referral_partner_id',
        'driver_id',
        'vehicle_id',
        'attribution_status',
        'attributed_at',
        'changed_at',
        'change_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'attribution_status' => ReferralAttributionStatus::class,
            'attributed_at' => 'datetime',
            'changed_at' => 'datetime',
        ];
    }

    public function referralEvent(): BelongsTo
    {
        return $this->belongsTo(ReferralEvent::class);
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function referralPartner(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('attribution_status', ReferralAttributionStatus::ACTIVE);
    }
}
