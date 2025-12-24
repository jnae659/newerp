<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ZatcaInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'zatca_uuid',
        'zatca_invoice_number',
        'zatca_invoice_type',
        'zatca_status',
        'zatca_data',
        'zatca_response',
        'zatca_qr_code',
        'zatca_digital_signature',
        'zatca_submitted_at',
        'zatca_validated_at',
        'zatca_error_message',
    ];

    protected $casts = [
        'zatca_data' => 'array',
        'zatca_response' => 'array',
        'zatca_submitted_at' => 'datetime',
        'zatca_validated_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function isValid()
    {
        return $this->zatca_status === 'valid';
    }

    public function isPending()
    {
        return $this->zatca_status === 'draft';
    }

    public function hasError()
    {
        return $this->zatca_status === 'invalid';
    }

    public function getInvoiceType()
    {
        return $this->zatca_invoice_type;
    }

    public function getUUID()
    {
        return $this->zatca_uuid;
    }
}
