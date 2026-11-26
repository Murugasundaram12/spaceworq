<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerInquiry extends Model
{
    public const TYPES = [
        'WORKSPACE_PROVIDER',
        'BUILDING_OWNER',
        'DEVELOPER',
    ];

    protected $fillable = [
        'user_id',
        'inquiry_type',
        'entity_name',
        'city_name',
        'locality',
        'total_desks',
        'desk_price',
        'total_area_sqft',
        'floor_plate_sqft',
        'partnership_model',
        'construction_stage',
        'expected_completion',
        'indicative_rent',
        'developer_brand',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'admin_notes',
        'handled_by',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'total_desks' => 'integer',
            'desk_price' => 'decimal:2',
            'total_area_sqft' => 'integer',
            'floor_plate_sqft' => 'integer',
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
