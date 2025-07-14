<x-filament::page>
     <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @if ($this->isCashier())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Split layout for Order Area and Payment Calculator -->
            <div class="grid grid-cols-1  gap-4">
                <!-- Left Column - Current Order Area -->
                <div class="">
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <h2 class="font-bold text-lg mb-2">Current Order Area</h2>
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-2 text-left">Qty</th>
                                    <th class="p-2 text-left">Product Name</th>
                                    <th class="p-2 text-left">Amount (PHP)</th>
                                    <th class="p-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="order-items">
                                <!-- Items will be added here dynamically -->
                            </tbody>
                        </table>

                        <div class="mt-4 text-right">
                            <h2 class="font-bold text-lg">Total</h2>
                            <h3 class="text-2xl font-bold" id="total-amount">PHP 0.00</h3>
                        </div>
                    </div>

                    <!-- Right Column - Payment Calculator -->
                    <div class="bg-gray-100 p-4 rounded-lg">
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

                        <!-- Payment Keypad -->
                        <div class="grid grid-cols-4 gap-2 ">
                            <div class="p-3 bg-white rounded shadow payment-btn">1</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">2</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">3</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">4</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">5</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">6</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">7</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">8</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">9</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">.</div>
                            <div class="p-3 bg-white rounded shadow payment-btn">0</div>
                            <div class="p-3 bg-red-500 text-white rounded shadow" id="clear-payment">C</div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 my-2">
                            {{-- <div class="p-3 bg-blue-500 text-white rounded shadow" id="add-payment">Add Payment</div> --}}
                            <div class="p-3 bg-green-500 text-white rounded shadow w-full" id="complete-payment">Complete
                                Payment</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Selection Area -->
            <div class="bg-gray-100 p-4 mt-4 rounded-lg">
                <h2 class="font-bold text-lg mb-2">Product Selection Area</h2>
                <div class="mb-4">
                    <div class="flex space-x-2 mb-2">
                        <span class="px-3 py-1 bg-blue-500 text-white rounded category-btn"
                            data-category="all">All</span>
                        <span class="px-3 py-1 bg-blue-500 text-white rounded category-btn"
                            data-category="prood">Prood</span>
                        <span class="px-3 py-1 bg-blue-500 text-white rounded category-btn"
                            data-category="sweet">Sweet</span>
                    </div>
                    <input type="text" placeholder="Search Product..." class="w-full p-2 border rounded"
                        id="product-search">
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="product-grid">
                    @foreach ($this->getProducts() as $product)
                        <div class="bg-white shadow rounded-lg p-4 cursor-pointer product-item" data-id="{{ $product->id }}"
                            data-name="{{ $product->name }}" data-price="{{ $product->price }}"
                            data-category="{{ strtolower($product->productCategory->type ?? '') }}">
                            <img src="{{ asset(path: 'storage/' . $product->image_path) }}"
                                class="w-full h-40 object-cover rounded mb-2" alt="">
                            <h2 class="font-bold text-lg">{{ $product->name }}</h2>
                            <p class="text-sm text-gray-600">{{ $product->description }}</p>
                            <span class="text-blue-600 text-xs">Code: {{ $product->code }}</span><br>
                            <span class="text-gray-400 text-xs">Category: {{ $product->productCategory->type ?? '—' }}</span>
                            <div class="mt-2 text-right font-bold">PHP {{ number_format($product->price, 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <script>
            // Current order data
            let currentOrder = [];
            let paymentAmount = 0;
            let paymentInput = '';

            // DOM elements
            const productItems = document.querySelectorAll('.product-item');
            const orderItemsTable = document.getElementById('order-items');
            const totalAmountElement = document.getElementById('total-amount');
            const displayTotal = document.getElementById('display-total');
            const displayReceived = document.getElementById('display-received');
            const displayChange = document.getElementById('display-change');
            const paymentButtons = document.querySelectorAll('.payment-btn');
            const clearPaymentButton = document.getElementById('clear-payment');
            const addPaymentButton = document.getElementById('add-payment');
            const completePaymentButton = document.getElementById('complete-payment');
            const productSearch = document.getElementById('product-search');
            const categoryButtons = document.querySelectorAll('.category-btn');

            // Initialize
            updateOrderDisplay();
            updatePaymentDisplay();

            // Add click event to product items
            productItems.forEach(item => {
                item.addEventListener('click', function () {
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');
                    const productPrice = parseFloat(this.getAttribute('data-price'));

                    const product = {
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: 1  // Default quantity is 1 when adding
                    };

                    console.log('Product selected:', product);

                    // Add to order
                    addToOrder(product);
                });
            });

            // Payment calculator buttons
            paymentButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const value = this.textContent;
                    paymentInput += value;
                    updatePaymentDisplay();
                });
            });

            // Clear payment
            clearPaymentButton.addEventListener('click', function () {
                paymentInput = '';
                updatePaymentDisplay();
            });

            // Add payment
            addPaymentButton.addEventListener('click', function () {
                if (paymentInput) {
                    const amount = parseFloat(paymentInput);
                    paymentAmount += amount;
                    paymentInput = '';
                    updatePaymentDisplay();
                }
            });

            // Complete payment
            completePaymentButton.addEventListener('click', function () {
                const total = calculateTotal();
                const change = paymentAmount - total;

                if (paymentAmount >= total) {
                    alert(`Payment completed! Change: PHP ${change.toFixed(2)}`);
                    console.log('Order completed:', currentOrder);
                    console.log('Payment received:', paymentAmount);
                    console.log('Change given:', change);

                    // Reset for next order
                    currentOrder = [];
                    paymentAmount = 0;
                    paymentInput = '';
                    updateOrderDisplay();
                    updatePaymentDisplay();
                } else {
                    alert(`Insufficient payment! Need PHP ${(total - paymentAmount).toFixed(2)} more.`);
                }
            });

            // Product search
            productSearch.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                filterProducts(searchTerm);
            });

            // Category filter
            categoryButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const category = this.getAttribute('data-category');
                    filterProducts('', category);
                });
            });

            // Helper function to add product to order
            function addToOrder(product) {
                // Check if product already exists in order
                const existingItem = currentOrder.find(item => item.id === product.id);

                if (existingItem) {
                    existingItem.quantity += product.quantity;
                } else {
                    currentOrder.push({ ...product });
                }

                updateOrderDisplay();
            }

            // Helper function to update the order display
            function updateOrderDisplay() {
                orderItemsTable.innerHTML = '';

                currentOrder.forEach((item, index) => {
                    const row = document.createElement('tr');
                    row.className = 'border-b';

                    row.innerHTML = `
                            <td class="p-2">
                                <div class="flex items-center">
                                    <button class="px-2 py-1 bg-gray-200 rounded decrease-qty" data-index="${index}">-</button>
                                    <span class="mx-2">${item.quantity}</span>
                                    <button class="px-2 py-1 bg-gray-200 rounded increase-qty" data-index="${index}">+</button>
                                </div>
                            </td>
                            <td class="p-2">${item.name}</td>
                            <td class="p-2">PHP ${(item.price * item.quantity).toFixed(2)}</td>
                            <td class="p-2">
                                <button class="px-2 py-1 bg-red-500 text-white rounded text-sm remove-item" data-index="${index}">Remove</button>
                            </td>
                        `;

                    orderItemsTable.appendChild(row);
                });

                // Add event listeners to quantity buttons
                document.querySelectorAll('.decrease-qty').forEach(button => {
                    button.addEventListener('click', function () {
                        const index = parseInt(this.getAttribute('data-index'));
                        if (currentOrder[index].quantity > 1) {
                            currentOrder[index].quantity--;
                            updateOrderDisplay();
                        }
                    });
                });

                document.querySelectorAll('.increase-qty').forEach(button => {
                    button.addEventListener('click', function () {
                        const index = parseInt(this.getAttribute('data-index'));
                        currentOrder[index].quantity++;
                        updateOrderDisplay();
                    });
                });

                // Add event listeners to remove buttons
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.addEventListener('click', function () {
                        const index = parseInt(this.getAttribute('data-index'));
                        currentOrder.splice(index, 1);
                        updateOrderDisplay();
                    });
                });

                // Update totals
                const total = calculateTotal();
                totalAmountElement.textContent = `PHP ${total.toFixed(2)}`;
                displayTotal.textContent = `PHP ${total.toFixed(2)}`;
            }

            // Helper function to update payment display
            function updatePaymentDisplay() {
                const currentInput = paymentInput ? parseFloat(paymentInput) : 0;
                const totalPayment = paymentAmount + currentInput;
                const total = calculateTotal();
                const change = totalPayment - total;

                displayReceived.textContent = `PHP ${totalPayment.toFixed(2)}`;
                displayChange.textContent = `PHP ${change > 0 ? change.toFixed(2) : '0.00'}`;
            }

            // Helper function to calculate total
            function calculateTotal() {
                return currentOrder.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            }

            // Helper function to filter products
            function filterProducts(searchTerm = '', category = 'all') {
                productItems.forEach(item => {
                    const name = item.getAttribute('data-name').toLowerCase();
                    const itemCategory = item.getAttribute('data-category');
                    const matchesSearch = searchTerm ? name.includes(searchTerm) : true;
                    const matchesCategory = category === 'all' || itemCategory === category;

                    if (matchesSearch && matchesCategory) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        </script>
    @else
        {{-- Default admin table --}}
        {{ $this->table }}
    @endif
</x-filament::page>