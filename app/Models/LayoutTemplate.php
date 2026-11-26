<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LayoutTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'elements',
        'elements_count',
        'total_seats',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'elements' => 'array',
            'elements_count' => 'integer',
            'total_seats' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(FloorTemplateMapping::class);
    }
}
