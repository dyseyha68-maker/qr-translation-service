<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_reference',
        'order_id',
        'referral_attribution_id',
        'referral_partner_id',
        'driver_id',
        'vehicle_id',
        'commission_rate',
        'eligible_amount',
        'commission_amount',
        'currency',
        'status',
        'earned_at',
        'approved_at',
        'paid_at',
        'reversed_at',
        'reversal_reason',
        'created_by',
        'approved_by',
        'paid_by',
        'reversed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommissionStatus::class,
            'commission_rate' => 'decimal:2',
            'eligible_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'earned_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function referralAttribution(): BelongsTo
    {
        return $this->belongsTo(ReferralAttribution::class);
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

    public function payments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class)->orderByDesc('paid_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('commission_reference', 'like', "%{$term}%")
                ->orWhereHas('order', function (Builder $oq) use ($term) {
                    $oq->where('order_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', function (Builder $cq) use ($term) {
                            $cq->where('name', 'like', "%{$term}%");
                        });
                })
                ->orWhereHas('referralPartner', function (Builder $pq) use ($term) {
                    $pq->where('name', 'like', "%{$term}%");
                })
                ->orWhereHas('driver', function (Builder $dq) use ($term) {
                    $dq->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopePartner(Builder $query, ?int $partnerId): Builder
    {
        if (! $partnerId) {
            return $query;
        }

        return $query->where('referral_partner_id', $partnerId);
    }

    public function scopeDriver(Builder $query, ?int $driverId): Builder
    {
        if (! $driverId) {
            return $query;
        }

        return $query->where('driver_id', $driverId);
    }
}
