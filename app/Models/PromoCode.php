<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'usage_limit_per_user',
        'valid_from',
        'valid_until',
        'is_active',
        'applicable_course_ids',
        'minimum_purchase_amount',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'minimum_purchase_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'applicable_course_ids' => 'array',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isValid($courseId = null, $amount = null)
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        if ($courseId && $this->applicable_course_ids && ! in_array($courseId, $this->applicable_course_ids)) {
            return false;
        }

        if ($amount && $this->minimum_purchase_amount && $amount < $this->minimum_purchase_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($amount)
    {
        if ($this->discount_type === 'percentage') {
            $discount = ($amount * $this->discount_value) / 100;

            if ($this->max_discount_amount) {
                $discount = min($discount, $this->max_discount_amount);
            }

            return round($discount, 2);
        }

        return min($this->discount_value, $amount);
    }
}
