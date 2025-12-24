<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ZatcaConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'zatca_enabled',
        'zatca_phase',
        'zatca_api_endpoint',
        'zatca_api_key',
        'zatca_api_secret',
        'zatca_certificate_path',
        'zatca_private_key_path',
        'zatca_tax_number',
        'zatca_branch_code',
        'zatca_device_id',
        'zatca_pos_device',
        'zatca_settings',
    ];

    protected $casts = [
        'zatca_settings' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function isEnabled()
    {
        return $this->zatca_enabled === 'on';
    }

    public function getPhase()
    {
        return $this->zatca_phase;
    }

    public function getSettings()
    {
        return $this->zatca_settings ?? [];
    }
}
