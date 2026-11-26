<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'gstin',
        'preferred_city_id',
        'billing_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferredCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'preferred_city_id');
    }
}
