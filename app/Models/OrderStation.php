<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'station_type',
        'printer_model',
        'printer_connection',
        'printer_ip',
        'printer_port',
        'paper_width_mm',
        'auto_print',
        'print_copies',
        'is_active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_print' => 'boolean',
            'is_active' => 'boolean',
            'print_copies' => 'integer',
            'paper_width_mm' => 'integer',
            'printer_port' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(KitchenBarTicket::class);
    }
}
