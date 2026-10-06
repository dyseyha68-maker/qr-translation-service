<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TelegramEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReferralEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code_id',
        'telegram_user_id',
        'event_type',
        'event_token',
        'telegram_update_id',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => TelegramEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'telegram_update_id' => 'integer',
        ];
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function attribution(): HasOne
    {
        return $this->hasOne(ReferralAttribution::class);
    }
}
