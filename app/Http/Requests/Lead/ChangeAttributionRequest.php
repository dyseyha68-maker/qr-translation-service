<?php

declare(strict_types=1);

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class ChangeAttributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeAttribution', $this->route('lead')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'referral_code_id' => ['required', 'integer', 'exists:referral_codes,id'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
