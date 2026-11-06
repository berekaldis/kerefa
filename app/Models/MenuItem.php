<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'category_id',
        'order_station_id',
        'code',
        'name',
        'description',
        'price',
        'cost_price',
        'preparation_department',
        'image_path',
        'is_available',
        'is_active',
        'recipe_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'recipe_enabled' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function orderStation(): BelongsTo
    {
        return $this->belongsTo(OrderStation::class, 'order_station_id');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(MenuItemModifier::class);
    }

    public function isKitchen(): bool
    {
        return $this->preparation_department === 'kitchen';
    }

    public function isBar(): bool
    {
        return $this->preparation_department === 'bar';
    }
}
