<?php

declare(strict_types=1);

namespace App\Http\Requests\ReferralCode;

use App\Models\ReferralCode;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ReferralCode|null $referralCode */
        $referralCode = $this->route('referral_code');

        return $referralCode !== null && ($this->user()?->can('update', $referralCode) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
