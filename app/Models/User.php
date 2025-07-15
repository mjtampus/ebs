<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'shift_start' => 'string',
            'shift_end' => 'string',   
        ];
    }

    protected static function booted()
    {
        static::saving(function (User $user) {
            \Log::info('User model saving:', [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'shift' => $user->shift,
                'shift_start' => $user->shift_start,
                'shift_end' => $user->shift_end,
                'dirty' => $user->getDirty(),
                'original' => $user->getOriginal()
            ]);
        });

        static::saved(function (User $user) {
            \Log::info('User model saved:', [
                'id' => $user->id,
                'shift' => $user->shift,
                'shift_start' => $user->shift_start,
                'shift_end' => $user->shift_end,
            ]);
        });
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
}