<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkspaceFloor extends Model
{
    protected $fillable = [
        'workspace_id',
        'floor_number',
        'label',
        'total_seats',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'floor_number' => 'integer',
            'total_seats' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function templateMapping(): HasOne
    {
        return $this->hasOne(FloorTemplateMapping::class, 'workspace_floor_id');
    }
}
