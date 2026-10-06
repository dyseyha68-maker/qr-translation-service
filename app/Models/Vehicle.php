<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_id',
        'vehicle_type',
        'plate_number',
        'make',
        'model',
        'color',
        'notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'status' => VehicleStatus::class,
        ];
    }

    /**
     * Normalize plate numbers on set and get.
     *
     * @return Attribute<string, string>
     */
    protected function plateNumber(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ?? '',
            set: fn (string $value) => strtoupper(trim((string) preg_replace('/\s+/', ' ', $value))),
        );
    }

    /**
     * Driver assigned to this vehicle.
     *
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Referral partner through the assigned driver.
     *
     * @return HasOneThrough<ReferralPartner, Driver, $this>
     */
    public function referralPartner(): HasOneThrough
    {
        return $this->hasOneThrough(
            ReferralPartner::class,
            Driver::class,
            'id', // Foreign key on Driver table...
            'id', // Foreign key on ReferralPartner table...
            'driver_id', // Local key on Vehicle table...
            'referral_partner_id' // Local key on Driver table...
        );
    }

    /**
     * Referral codes assigned to this vehicle.
     *
     * @return HasMany<ReferralCode, $this>
     */
    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class);
    }

    /**
     * Determine if vehicle is active.
     */
    public function isActive(): bool
    {
        return $this->status === VehicleStatus::ACTIVE;
    }

    /**
     * Scope query to only active vehicles.
     *
     * @param  Builder<Vehicle>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', VehicleStatus::ACTIVE->value);
    }
}
