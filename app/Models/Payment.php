<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'transaction_id',
        'payment_gateway',
        'gateway_transaction_id',
        'amount',
        'currency',
        'platform_fee',
        'instructor_amount',
        'status',
        'failure_reason',
        'promo_code_id',
        'discount_amount',
        'metadata',
        'receipt_url',
        'invoice_number',
        'refunded_at',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'instructor_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'metadata' => 'array',
        'refunded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = 'TXN-'.Str::upper(Str::random(12));
            }
            if (empty($payment->invoice_number)) {
                $payment->invoice_number = 'INV-'.date('Ymd').'-'.Str::upper(Str::random(6));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
