<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'lead_id',
        'referral_attribution_id',
        'status',
        'currency',
        'subtotal',
        'discount',
        'total_amount',
        'notes',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'paid_amount',
        'outstanding_amount',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function referralAttribution(): BelongsTo
    {
        return $this->belongsTo(ReferralAttribution::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->items();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Total amount of valid (non-refunded) payments made toward this order.
     */
    public function getPaidAmountAttribute(): float
    {
        $paid = $this->payments
            ->where('status', PaymentRecordStatus::PAID)
            ->sum(fn (Payment $p) => (float) $p->amount);

        return round((float) $paid, 2);
    }

    /**
     * Remaining unpaid balance for this order.
     */
    public function getOutstandingAmountAttribute(): float
    {
        $outstanding = (float) $this->total_amount - $this->paid_amount;

        return max(0.0, round($outstanding, 2));
    }

    /**
     * Compute current order payment state based on transactions.
     */
    public function getPaymentStatusAttribute(): PaymentStatus
    {
        $paid = $this->paid_amount;
        $total = (float) $this->total_amount;

        if ($paid >= $total && $total > 0) {
            return PaymentStatus::PAID;
        }

        if ($paid > 0 && $paid < $total) {
            return PaymentStatus::PARTIAL;
        }

        $allRefunded = $this->payments->isNotEmpty() &&
            $this->payments->every(fn (Payment $p) => $p->status === PaymentRecordStatus::REFUNDED);

        if ($allRefunded) {
            return PaymentStatus::REFUNDED;
        }

        return PaymentStatus::UNPAID;
    }

    /**
     * Determine if the order has been completely paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= (float) $this->total_amount && (float) $this->total_amount > 0;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%")
                ->orWhereHas('customer', function (Builder $cq) use ($term) {
                    $cq->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                })
                ->orWhereHas('referralAttribution.driver', function (Builder $dq) use ($term) {
                    $dq->where('name', 'like', "%{$term}%");
                })
                ->orWhereHas('referralAttribution.referralCode', function (Builder $rq) use ($term) {
                    $rq->where('code', 'like', "%{$term}%");
                });
        });
    }

    public function scopeWherePaymentStatus(Builder $query, ?string $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return match ($status) {
            PaymentStatus::PAID->value => $query->whereRaw(
                '(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.order_id = orders.id AND payments.status = ?) >= orders.total_amount AND orders.total_amount > 0',
                [PaymentRecordStatus::PAID->value]
            ),
            PaymentStatus::PARTIAL->value => $query->whereRaw(
                '(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.order_id = orders.id AND payments.status = ?) > 0 AND (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.order_id = orders.id AND payments.status = ?) < orders.total_amount',
                [PaymentRecordStatus::PAID->value, PaymentRecordStatus::PAID->value]
            ),
            PaymentStatus::REFUNDED->value => $query->whereHas('payments', function ($q) {
                $q->where('status', PaymentRecordStatus::REFUNDED->value);
            })->whereDoesntHave('payments', function ($q) {
                $q->where('status', PaymentRecordStatus::PAID->value);
            }),
            PaymentStatus::UNPAID->value => $query->whereDoesntHave('payments', function ($q) {
                $q->where('status', PaymentRecordStatus::PAID->value);
            })->whereDoesntHave('payments', function ($q) {
                $q->where('status', PaymentRecordStatus::REFUNDED->value);
            }),
            default => $query,
        };
    }
}

