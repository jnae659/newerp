<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountantService extends Model
{
    use HasFactory;

    protected $fillable = [
        'accountant_id',
        'service_name',
        'description',
        'category',
        'hourly_rate',
        'monthly_rate',
        'fixed_rate',
        'is_available',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'fixed_rate' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    /**
     * Get the accountant that owns this service.
     */
    public function accountant()
    {
        return $this->belongsTo(User::class, 'accountant_id');
    }
}
