<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceImage extends Model
{
    protected $fillable = [
        'workspace_id',
        'disk',
        'storage_path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'sort_order',
        'is_primary',
    ];

    protected $hidden = [
        'disk',
        'storage_path',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
