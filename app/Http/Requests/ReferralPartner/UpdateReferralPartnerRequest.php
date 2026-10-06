<?php

declare(strict_types=1);

namespace App\Http\Requests\ReferralPartner;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use App\Models\ReferralPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReferralPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ReferralPartner|null $partner */
        $partner = $this->route('referral_partner');

        return $partner !== null && ($this->user()?->can('update', $partner) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(PartnerType::values())],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', Rule::in(PartnerStatus::values())],
        ];
    }
}
