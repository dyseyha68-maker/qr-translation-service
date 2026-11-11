<?php

declare(strict_types=1);

namespace App\Http\Requests\ReferralCode;

use App\Models\ReferralCode;
use Illuminate\Foundation\Http\FormRequest;

class BatchGenerateReferralCodeRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'prefix' => ['nullable', 'string', 'max:16', 'regex:/^[A-Za-z0-9_-]+$/'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}

