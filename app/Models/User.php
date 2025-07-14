<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'contact',
        'gender',
        'shift',
        'shift_start',
        'shift_end',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'staff';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
         'admin' => $this->isAdmin(),       
            'cashier' => $this->isCashier(), 
        default => false,
    };
}   
protected static function booted()
{
    static::saving(function (User $user) {
        if ($user->role === 'cashier') {
            if (
                empty($user->shift) ||
                empty($user->shift_start) ||
                empty($user->shift_end)
            ) {
                throw new \InvalidArgumentException('Cashiers must have a shift, shift start, and shift end time.');
            }
        }
    });
}
}
