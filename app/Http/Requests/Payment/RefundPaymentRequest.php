<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::PAYMENTS_REFUND->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'refund_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
