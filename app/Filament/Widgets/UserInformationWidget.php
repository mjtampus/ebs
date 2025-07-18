<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class UserInformationWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $isAdmin = ($user->role === 'admin') ? 'Admin Name' : 'Employee Name';

        $color = ($user->role === 'admin') ? 'success' : 'info';

        $stats = [];

        $stats[] = Stat::make($isAdmin, ucfirst($user->name) ?? 'N/A')
            ->description('Logged in as')
            ->descriptionIcon('heroicon-m-user')
            ->color($color)
            ->extraAttributes([
                'class' => 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/50 dark:to-indigo-900/50',
            ]);

        $stats[] = Stat::make(($isAdmin === 'Admin Name' ? 'Role' : 'Employee Role'), ucfirst($user->role ?? 'Staff'))
            ->description('Access level')
            ->descriptionIcon('heroicon-m-shield-check')
            ->color($this->getRoleColor($user->role))
            ->extraAttributes([
                'class' => 'bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/50 dark:to-pink-900/50',
            ]);

        if ($user->shift && $user->shift_start && $user->shift_end) {
            $formattedShift = ucfirst($user->shift);
            $formattedStart = Carbon::parse($user->shift_start)->format('g:i A');
            $formattedEnd = Carbon::parse($user->shift_end)->format('g:i A');

            $stats[] = Stat::make("Shift", $formattedShift)
                ->description("From $formattedStart to $formattedEnd")
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getShiftColor($user->shift))
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/50 dark:to-emerald-900/50',
                ]);

            $status = $this->getWorkStatus();
            $stats[] = Stat::make('Status', $status)
                ->description('Current work status')
                ->descriptionIcon($this->getStatusIcon($status))
                ->color($this->getStatusColor($status))
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/50 dark:to-orange-900/50',
                ]);
        }

        return $stats;
    }

    protected function getHeading(): ?string
    {
        return 'User Information Overview';
    }

    protected function getShiftColor(string $shift): string
    {
        return match(strtolower($shift)) {
            'morning' => 'success',
            'afternoon' => 'warning',
            'evening', 'night' => 'danger',
            default => 'info',
        };
    }

    protected function getRoleColor(?string $role): string
    {
        return match(strtolower($role ?? 'staff')) {
            'admin' => 'success',
            'cashier' => 'warning',
            'staff' => 'info',
            default => 'success',
        };
    }

    protected function getWorkStatus(): string
    {
        $timezone = config('app.timezone');
        $user = auth()->user();
        $now = now($timezone);

        $shiftStart = Carbon::parse($user->shift_start)
            ->timezone($timezone);
        $shiftEnd = Carbon::parse($user->shift_end)
            ->timezone($timezone);

        if ($now->between($shiftStart, $shiftEnd)) {
            return 'On Duty';
        } elseif ($now->lt($shiftStart)) {
            return 'Before Shift';
        }

        return 'After Shift';
    }

    protected function getStatusIcon(string $status): string
    {
        return match($status) {
            'On Duty' => 'heroicon-m-check-circle',
            'Before Shift' => 'heroicon-m-clock',
            'After Shift' => 'heroicon-m-x-circle',
            default => 'heroicon-m-information-circle',
        };
    }

    protected function getStatusColor(string $status): string
    {
        return match($status) {
            'On Duty' => 'success',
            'Before Shift' => 'warning',
            'After Shift' => 'gray',
            default => 'info',
        };
    }
}
