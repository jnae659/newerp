<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'percentage',
        'level1_percentage',
        'level2_percentage',
        'min_payout',
        'is_enable',
        'guideline',
        'created_by',
    ];
}
