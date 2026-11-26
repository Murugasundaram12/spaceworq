<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FloorTemplateMapping extends Model
{
    protected $fillable = [
        'provider_id',
        'workspace_id',
        'workspace_floor_id',
        'layout_template_id',
        'mapped_by',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(WorkspaceFloor::class, 'workspace_floor_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LayoutTemplate::class, 'layout_template_id');
    }

    public function mapper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mapped_by');
    }
}
