<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_PROCESSED = 'PROCESSED';
    public const STATUS_IGNORED = 'IGNORED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'signature_verified',
        'payload',
        'processed_at',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
