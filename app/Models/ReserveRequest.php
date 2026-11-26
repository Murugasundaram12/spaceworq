<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReserveRequest extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'Open',
        'In Progress',
        'Review',
        'Discussion',
        'Completed',
        'Cancelled',
        'Rejected',
    ];

    protected $fillable = [
        'request_code',
        'workspace_id',
        'provider_id',
        'customer_user_id',
        'assigned_provider_user_id',
        'status',
        'requested_seats',
        'move_in_date',
        'duration_months',
        'request_summary',
        'remarks',
        'reminders_count',
        'last_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_seats' => 'integer',
            'move_in_date' => 'date',
            'duration_months' => 'integer',
            'reminders_count' => 'integer',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(ProviderUser::class, 'assigned_provider_user_id');
    }
}
