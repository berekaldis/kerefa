<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'category',
        'key',
        'value',
        'type',
        'description',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function get(string $key, mixed $default = null, ?int $branchId = null): mixed
    {
        $setting = static::where('key', $key)
            ->where(function ($query) use ($branchId) {
                if ($branchId !== null) {
                    $query->where('branch_id', $branchId)->orWhereNull('branch_id');
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->orderByRaw('branch_id IS NULL ASC') // specific branch first
            ->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?string $category = 'general', ?int $branchId = null): self
    {
        $stringValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        return static::updateOrCreate(
            ['branch_id' => $branchId, 'key' => $key],
            [
                'category' => $category ?? 'general',
                'value' => $stringValue,
                'type' => $type,
            ]
        );
    }
}
