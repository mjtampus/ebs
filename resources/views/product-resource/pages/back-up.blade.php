{{-- <x-filament::page>
    @livewire('cashier-opening-float')

    @script
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('cashierDataLoaded', (data) => {
                window.cashierData = data;
                console.log('Cashier data loaded:', data);
            });
        });
    </script>
    @endscript
    @script
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('cashierDataLoaded', (data) => {
                window.cashierData = data;
                console.log('Cashier data loaded:', data);
            });
        });
    </script>
    @endscript
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border ">
        <div class="grid grid-cols-1 gap-4">
            <!-- Order Area -->
            <div class=" p-4 rounded-lg">
                <h2 class="font-bold text-lg mb-2">Current Order Area</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-500 text-white">
                            <th class="p-2 text-left">Qty</th>
                            <th class="p-2 text-left">Product Name</th>
                            <th class="p-2 text-left">Unit Price (PHP)</th>
                            <th class="p-2 text-left">Amount (PHP)</th>
                            <th class="p-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="order-items"></tbody>
                </table>
                <div class="mt-4 text-right">
                    <h2 class="font-bold text-lg">Total</h2>
                    <h3 class="text-2xl font-bold" id="total-amount">PHP 0.00</h3>
                </div>
            </div>

            <!-- Payment Calculator -->
            <div class=" p-4 rounded-lg">
                <h2 class="font-bold text-lg mb-2">Payment Calculator</h2>
                <div class="mb-4">
                    <div class="flex justify-between mb-2">
                        <span>Total Amount:</span>
                        <span id="display-total">PHP 0.00</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>Amount Received:</span>
                        <span id="display-received">PHP 0.00</span>
                    </div>
                    <div class="flex justify-between font-bold text-lg">
                        <span>Change:</span>
                        <span id="display-change">PHP 0.00</span>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2">
                    <div class="p-3  rounded shadow payment-btn">1</div>
                    <div class="p-3  rounded shadow payment-btn">2</div>
                    <div class="p-3  rounded shadow payment-btn">3</div>
                    <div class="p-3  rounded shadow payment-btn">4</div>
                    <div class="p-3  rounded shadow payment-btn">5</div>
                    <div class="p-3  rounded shadow payment-btn">6</div>
                    <div class="p-3  rounded shadow payment-btn">7</div>
                    <div class="p-3  rounded shadow payment-btn">8</div>
                    <div class="p-3  rounded shadow payment-btn">9</div>
                    <div class="p-3  rounded shadow payment-btn">.</div>
                    <div class="p-3  rounded shadow payment-btn">0</div>
                    <div class="p-3 bg-red-500 text-white rounded shadow" id="clear-payment">C</div>
                    <div class="p-3 bg-yellow-500 text-white rounded shadow" id="backspace-payment">←</div>
                </div>

                <div class="grid grid-cols-1 gap-2 my-2">
                    <div class="p-3 bg-green-500 text-white rounded shadow w-full text-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        id="complete-payment">
                        Complete Payment
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Selection Area -->
        <div class=" p-4 mt-4 rounded-lg">
            <h2 class="font-bold text-lg mb-2">Product Selection Area</h2>
            <div class="mb-4">
                @php
                    $categories = \App\Models\ProductCategories::where('has_unit', 0)->get();
                @endphp
                <div class="flex space-x-2 mb-2">
                    <span class="px-3 py-1 bg-gray-500 text-white rounded category-btn cursor-pointer"
                        data-category-id="all">All</span>
                    @foreach ($categories as $category)
                        <span class="px-3 py-1 bg-blue-500 text-white rounded category-btn cursor-pointer"
                            data-category-id="{{ $category->id }}">
                            {{ $category->type }}
                        </span>
                    @endforeach
                </div>
                <input type="text" placeholder="Search Product..." id="product-search" class="w-full p-2 border rounded
           bg-white text-black
           dark:bg-gray-800 dark:text-white
           placeholder-gray-400 dark:placeholder-gray-300" />

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-4" id="product-grid">
                @foreach ($this->getProducts() as $product)
                    @if ($product->unit === 'pcs')
                        <div class=" shadow rounded-lg p-4 cursor-pointer product-item" data-id="{{ $product->id }}"
                            data-name="{{ $product->name }}" data-price="{{ $product->unit_price }}"
                            data-stock="{{ $product->product_Stock->stock }}" data-category-id="{{ $product->category_id }}
                                            ">

                            <div class="h-[250px]">
                                <img src="{{ asset('storage/' . $product->image_path) }}"
                                    class="w-full h-full object-cover rounded mb-2" alt="">
                            </div>


                            <h2 class="font-bold text-lg">{{ $product->name }}</h2>
                            <div class="mt-2 text-right font-bold">PHP {{ number_format($product->unit_price, 2) }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <script src="{{ asset('js/cashier-list-products.js') }}"></script>
</x-filament::page> --}}