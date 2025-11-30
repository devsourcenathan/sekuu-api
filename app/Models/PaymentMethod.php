<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway',
        'gateway_payment_method_id',
        'type',
        'is_default',
        'metadata',
        'last_four',
        'brand',
        'expires_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
        'expires_at' => 'date',
    ];

    /**
     * Get the user that owns this payment method
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set this payment method as the default
     */
    public function setAsDefault(): void
    {
        // Remove default from all other payment methods for this user
        $this->user->paymentMethods()
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this one as default
        $this->update(['is_default' => true]);
    }

    /**
     * Check if this payment method is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get a display name for this payment method
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'mobile_money') {
            $provider = $this->metadata['provider'] ?? 'Mobile Money';
            $phone = $this->metadata['phone_number'] ?? '';
            return ucfirst($provider) . ' - ' . substr($phone, -4);
        }

        if ($this->type === 'card') {
            return ($this->brand ?? 'Card') . ' •••• ' . $this->last_four;
        }

        return ucfirst($this->type);
    }
}
