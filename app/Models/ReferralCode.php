<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReferralCodeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralCode extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'referral_partner_id',
        'driver_id',
        'vehicle_id',
        'status',
        'label',
        'notes',
        'created_by',
        'deactivated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ReferralCodeStatus::class,
        'deactivated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Set the code attribute in uppercase and trimmed format.
     */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Check if the referral code is active.
     */
    public function isActive(): bool
    {
        return $this->status === ReferralCodeStatus::ACTIVE;
    }

    /**
     * Scope a query to only include active referral codes.
     *
     * @param  Builder<ReferralCode>  $query
     * @return Builder<ReferralCode>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ReferralCodeStatus::ACTIVE->value);
    }

    /**
     * Referral partner relationship.
     *
     * @return BelongsTo<ReferralPartner, ReferralCode>
     */
    public function referralPartner(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class);
    }

    /**
     * Driver relationship.
     *
     * @return BelongsTo<Driver, ReferralCode>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Vehicle relationship.
     *
     * @return BelongsTo<Vehicle, ReferralCode>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Creator relationship.
     *
     * @return BelongsTo<User, ReferralCode>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ReferralEvent>
     */
    public function referralEvents(): HasMany
    {
        return $this->hasMany(ReferralEvent::class);
    }

    /**
     * @return HasMany<ReferralAttribution>
     */
    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class);
    }
}
