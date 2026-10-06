<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'language_code',
        'is_bot',
        'phone',
        'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'is_bot' => 'boolean',
            'last_interaction_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return $this->username ? "@{$this->username}" : "User #{$this->telegram_id}";
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function referralEvents(): HasMany
    {
        return $this->hasMany(ReferralEvent::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class);
    }
}
