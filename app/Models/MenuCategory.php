<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'order_station_id',
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orderStation(): BelongsTo
    {
        return $this->belongsTo(OrderStation::class, 'order_station_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'category_id')->orderBy('name');
    }
}
