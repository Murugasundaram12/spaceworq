<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceSubcategory extends Model
{
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'price_unit_label',
        'supports_upcoming',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'supports_upcoming' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkspaceCategory::class, 'category_id');
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'subcategory_id');
    }
}
