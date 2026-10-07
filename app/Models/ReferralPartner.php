<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralPartner extends Model
{
    /** @use HasFactory<\Database\Factories\ReferralPartnerFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'commission_rate',
        'phone',
        'email',
        'company_name',
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
            'type' => PartnerType::class,
            'status' => PartnerStatus::class,
            'commission_rate' => 'decimal:2',
        ];
    }

    /**
     * Drivers associated with this referral partner.
     *
     * @return HasMany<Driver, $this>
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Historical commissions earned by referrals from this partner.
     *
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * @return HasMany<ReferralCode, ReferralPartner>
     */
    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class);
    }

    /**
     * Determine if the partner is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === PartnerStatus::ACTIVE;
    }

    /**
     * Scope query to only active partners.
     *
     * @param  Builder<ReferralPartner>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', PartnerStatus::ACTIVE->value);
    }
}
