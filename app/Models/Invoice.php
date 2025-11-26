<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'user_id',
        'invoice_number',
        'pdf_path',
        'billing_name',
        'billing_email',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_zip',
        'tax_id',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
