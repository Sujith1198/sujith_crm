<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('id');
        return [
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|email|max:255|unique:users,email,{$userId}",
            'phone'    => 'nullable|string|max:20',
            'password' => ['nullable', Password::min(8)->letters()->numbers(), 'confirmed'],
            'role'     => 'sometimes|string|in:admin,user',
            'status'   => 'sometimes|in:active,inactive,suspended',
            'timezone' => 'sometimes|string|max:50',
        ];
    }
}
