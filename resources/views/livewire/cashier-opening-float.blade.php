<div>
    @if ($showModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg w-96 shadow-lg">
                <h2 class="text-lg font-bold mb-4 text-black dark:text-white">Enter Opening Float</h2>

                {{-- Render Filament form --}}
                {{ $this->form }}

                <div class="mt-4 text-right">
                    <span wire:click="save"
                        class="bg-green-500 dark:bg-green-600 cursor-pointer text-white dark:text-black hover:bg-green-600 dark:hover:bg-green-700 font-bold px-4 py-2 rounded">
                        Save
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>
