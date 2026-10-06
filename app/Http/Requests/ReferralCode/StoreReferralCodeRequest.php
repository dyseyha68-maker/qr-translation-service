<?php

declare(strict_types=1);

namespace App\Http\Requests\ReferralCode;

use App\Enums\ReferralCodeStatus;
use App\Models\ReferralCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReferralCode::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'referral_partner_id' => ['required', 'integer', 'exists:referral_partners,id'],
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'code' => ['nullable', 'string', 'max:64', 'unique:referral_codes,code'],
            'label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', Rule::in(ReferralCodeStatus::values())],
        ];
    }
}
