<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenBarTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'order_id',
        'ticket_number',
        'department',
        'status',
        'sent_at',
        'started_at',
        'ready_at',
        'collected_at',
        'reprint_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
            'collected_at' => 'datetime',
            'reprint_count' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TicketItem::class, 'ticket_id');
    }

    public function isKitchen(): bool
    {
        return $this->department === 'kitchen';
    }

    public function isBar(): bool
    {
        return $this->department === 'bar';
    }
}
