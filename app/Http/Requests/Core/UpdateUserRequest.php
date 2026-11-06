<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', Password::defaults()],
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'accessible_branches' => ['nullable', 'array'],
            'accessible_branches.*' => ['exists:branches,id'],
        ];
    }
}
