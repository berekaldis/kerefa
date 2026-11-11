<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'category_id',
        'code',
        'name',
        'unit',
        'current_stock',
        'min_stock_level',
        'unit_cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:4',
            'min_stock_level' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }
}
