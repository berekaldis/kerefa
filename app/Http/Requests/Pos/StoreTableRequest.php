<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pos.tables.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dining_area_id' => ['required', 'exists:dining_areas,id'],
            'table_number' => ['required', 'string', 'max:30'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:available,occupied,reserved,maintenance'],
        ];
    }
}
