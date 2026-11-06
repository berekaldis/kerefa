<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('branches.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = $this->route('branch')?->id ?? $this->route('branch');

        return [
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('branches')->ignore($branchId)],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'is_main' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
