<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceCategory extends Model
{
    public const CODE_LONG_TERM_LEASING = 'LONG_TERM_LEASING';
    public const CODE_DAY_PASS = 'DAY_PASS';
    public const CODE_MEETINGS_EVENTS = 'MEETINGS_EVENTS';
    public const CODE_VIRTUAL_OFFICE = 'VIRTUAL_OFFICE';

    protected $fillable = [
        'code',
        'name',
        'subtitle',
        'description',
        'badge_label',
        'image_path',
        'default_booking_mode',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(WorkspaceSubcategory::class, 'category_id')->orderBy('sort_order');
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'category_id');
    }
}
