<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User|null $targetUser */
        $targetUser = $this->route('user');

        return $targetUser !== null && ($this->user()?->can('update', $targetUser) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($targetUser->id),
            ],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', 'string', Rule::in(RoleName::values())],
            'status' => ['required', 'string', Rule::in(UserStatus::values())],
        ];
    }
}
