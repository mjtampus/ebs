<?php

namespace App\Filament\Cashier\Pages;

use Filament\Pages\Page;
use App\Models\OpeningFloat;
use Illuminate\Support\Facades\Auth;

class Cash extends Page
{
    protected static ?string $title = 'Cash';
    protected static string $view = 'filament.cashier.pages.cash';
    protected static ?string $slug = 'cashier'; // This sets the URL slug
    protected static ?string $navigationLabel = 'Cash'; // Optional, for navigation

    public $showOpeningFloatModal = false;
    public $openingFloatAmount;

    public function mount(): void
    {
        $user = Auth::user();

        // Check if user is cashier and hasn't entered opening float today
        if ($user->isCashier()) {
            $todayFloat = $user->openingFloats()->whereDate('date', now())->first();
            if (!$todayFloat) {
                $this->showOpeningFloatModal = true;
            }
        }
    }

    public function saveOpeningFloat()
    {
        $this->validate([
            'openingFloatAmount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        $user->openingFloats()->create([
            'amount' => $this->openingFloatAmount,
            'date' => now(),
        ]);

        $this->showOpeningFloatModal = false;

        $this->notify('success', 'Opening float saved successfully.');
    }
}
