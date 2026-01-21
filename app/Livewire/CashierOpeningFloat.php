<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use App\Models\OpeningFloat;
use Carbon\Carbon;

class CashierOpeningFloat extends Component implements HasForms
{
    use InteractsWithForms;

    public bool $showModal = false;

    // Form fields
    public ?float $openingFloatAmount = null;

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('openingFloatAmount')
                ->label('Opening Float Amount')
                ->numeric()
                ->required()
                ->autofocus(), // Auto-focus the input when modal opens
        ];
    }

    public function mount(): void
    {
        $todayFloat = OpeningFloat::where('user_id', auth()->id())
            ->whereDate('created_at', Carbon::today())
            ->first();

        if (!$todayFloat) {
            $this->showModal = true;
        }

        // Initialize the form
        $this->form->fill([]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        OpeningFloat::create([
            'user_id' => auth()->id(),
            'amount' => $data['openingFloatAmount'],
            'date' => now(),
        ]);

        $this->showModal = false;

        // Use dispatch() instead of dispatchBrowserEvent() for Livewire v3
        $this->dispatch('floatSaved');
    }

    public function render()
    {
        return view('livewire.cashier-opening-float');
    }
}