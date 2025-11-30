<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'preferred_currency',
        'stripe_customer_id',
        'payout_method',
        'payout_currency',
        'payout_schedule',
        'payout_threshold',
        'payout_details',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'payout_details' => 'array',
        'payout_threshold' => 'decimal:2',
    ];

    // Relations
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user')->withTimestamps();
    }

    // Permission methods
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }

        return $this->roles->contains('id', $role);
    }

    public function hasAnyRole($roles)
    {
        return $this->roles->whereIn('slug', $roles)->isNotEmpty();
    }

    public function hasPermission($permission)
    {
        // Check direct permissions
        if (is_string($permission)) {
            if ($this->permissions->contains('slug', $permission)) {
                return true;
            }
        } else {
            if ($this->permissions->contains('id', $permission)) {
                return true;
            }
        }

        // Check role permissions
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        if (! $this->roles->contains($role->id)) {
            $this->roles()->attach($role->id);
        }
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
    }

    public function givePermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }

        if (! $this->permissions->contains($permission->id)) {
            $this->permissions()->attach($permission->id);
        }
    }

    public function revokePermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }

    public function instructedCourses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function testSubmissions()
    {
        return $this->hasMany(TestSubmission::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    // Currency helper methods
    public function getPreferredCurrency(): string
    {
        return $this->preferred_currency ?? 'USD';
    }

    // Instructor payout helper methods
    public function getPayoutDetails(): array
    {
        return $this->payout_details ?? [];
    }

    public function updatePayoutSettings(array $settings): void
    {
        $this->update([
            'payout_method' => $settings['method'],
            'payout_currency' => $settings['currency'],
            'payout_schedule' => $settings['schedule'],
            'payout_threshold' => $settings['threshold'],
            'payout_details' => $settings['details'],
        ]);
    }

    public function canRequestPayout(): bool
    {
        $earnings = $this->calculatePendingEarnings();
        return $earnings >= $this->payout_threshold;
    }

    public function calculatePendingEarnings(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->whereNull('payout_id')
            ->sum('instructor_amount');
    }
}
