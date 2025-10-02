<x-filament::page>
    <x-filament::modal
        wire:model="showOpeningFloatModal"
        title="Enter Opening Float"
        :can-close="false"
    >
        <x-filament::form>
            <x-filament::text-input
                label="Opening Float Amount"
                wire:model.defer="openingFloatAmount"
                type="number"
                min="0"
                required
            />
        </x-filament::form>

        <x-slot name="footer">
            <x-filament::button wire:click="saveOpeningFloat" color="primary">
                Save
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Your regular cashier page content goes here --}}
    <h2>Welcome, {{ auth()->user()->name }}</h2>
</x-filament::page>
