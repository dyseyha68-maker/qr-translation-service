<?php

declare(strict_types=1);

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;

class AdjustCommissionRateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('commission')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'commission_rate' => 'commission rate',
            'reason' => 'adjustment reason',
        ];
    }
}
