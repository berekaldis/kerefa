<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_item_id',
        'name',
        'yield_portions',
        'total_cost',
        'cost_per_portion',
        'food_cost_percentage',
        'instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'yield_portions' => 'decimal:2',
            'total_cost' => 'decimal:4',
            'cost_per_portion' => 'decimal:4',
            'food_cost_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
