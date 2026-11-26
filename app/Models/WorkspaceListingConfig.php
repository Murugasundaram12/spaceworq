<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceListingConfig extends Model
{
    protected $fillable = [
        'workspace_id',
        'subcategory_id',
        'price_per_day',
        'price_per_month',
        'price_per_hour',
        'price_per_half_day',
        'price_per_sqft',
        'price_per_year',
        'min_seats',
        'max_seats',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'price_per_month' => 'decimal:2',
            'price_per_hour' => 'decimal:2',
            'price_per_half_day' => 'decimal:2',
            'price_per_sqft' => 'decimal:2',
            'price_per_year' => 'decimal:2',
            'min_seats' => 'integer',
            'max_seats' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(WorkspaceSubcategory::class, 'subcategory_id');
    }
}
