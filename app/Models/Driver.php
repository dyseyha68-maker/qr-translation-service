<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    /** @use HasFactory<\Database\Factories\DriverFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'referral_partner_id',
        'name',
        'phone',
        'email',
        'national_id',
        'address',
        'notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
        ];
    }

    /**
     * Referral partner this driver belongs to.
     *
     * @return BelongsTo<ReferralPartner, $this>
     */
    public function referralPartner(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class);
    }

    /**
     * Vehicles registered under this driver.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Referral codes assigned to this driver.
     *
     * @return HasMany<ReferralCode, $this>
     */
    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class);
    }

    /**
     * Determine if driver is active.
     */
    public function isActive(): bool
    {
        return $this->status === DriverStatus::ACTIVE;
    }

    /**
     * Scope query to only active drivers.
     *
     * @param  Builder<Driver>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', DriverStatus::ACTIVE->value);
    }
}
