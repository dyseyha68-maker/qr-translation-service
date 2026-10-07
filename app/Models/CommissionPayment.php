<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionPaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'payment_reference',
        'amount',
        'currency',
        'payment_method',
        'paid_at',
        'paid_to',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => CommissionPaymentMethod::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
