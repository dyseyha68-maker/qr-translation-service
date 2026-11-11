<?php

declare(strict_types=1);

namespace App\Http\Requests\ReferralCode;

use App\Enums\VehicleType;
use App\Models\ReferralCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignDriverToCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('referralCode')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'driver_name' => ['required', 'string', 'max:255'],
            'driver_phone' => ['required', 'string', 'max:50'],
            'vehicle_plate' => ['required', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', Rule::in(VehicleType::values())],
            'referral_partner_id' => ['nullable', 'integer', 'exists:referral_partners,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

