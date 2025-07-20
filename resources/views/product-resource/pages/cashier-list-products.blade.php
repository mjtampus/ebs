<x-filament::page>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border">
        <div class="grid grid-cols-1 gap-4">
            <!-- Order Area -->
            <div class="bg-gray-100 p-4 rounded-lg">
                <h2 class="font-bold text-lg mb-2">Current Order Area</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
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

                <div class="grid grid-cols-4 gap-2">
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
        <div class="bg-gray-100 p-4 mt-4 rounded-lg">
            <h2 class="font-bold text-lg mb-2">Product Selection Area</h2>
            <div class="mb-4">
                @php $categories = \App\Models\ProductCategories::all(); @endphp
                <div class="flex space-x-2 mb-2">
                    <span class="px-3 py-1 bg-gray-500 text-white rounded category-btn" data-category="all">All</span>
                    @foreach ($categories as $category)
                        <span class="px-3 py-1 bg-blue-500 text-white rounded category-btn"
                            data-category="{{ strtolower($category->type) }}">
                            {{ $category->type }}
                        </span>
                    @endforeach
                </div>
                <input type="text" placeholder="Search Product..." class="w-full p-2 border rounded"
                    id="product-search">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-4" id="product-grid">
                @foreach ($this->getProducts() as $product)
                    <div class="bg-white shadow rounded-lg p-4 cursor-pointer product-item" data-id="{{ $product->id }}"
                        data-name="{{ $product->name }}" data-price="{{ $product->unit_price }}"
                        data-category="{{ strtolower($product->productCategory->type ?? '') }}">
                        <img src="{{ asset('storage/' . $product->image_path) }}"
                            class="w-full h-40 object-cover rounded mb-2" alt="">
                        <h2 class="font-bold text-lg">{{ $product->name }}</h2>
                        <div class="mt-2 text-right font-bold">PHP {{ number_format($product->unit_price, 2) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>
        let currentOrder = [], paymentAmount = 0, paymentInput = '';

        const productItems = document.querySelectorAll('.product-item');
        const orderItemsTable = document.getElementById('order-items');
        const totalAmountElement = document.getElementById('total-amount');
        const displayTotal = document.getElementById('display-total');
        const displayReceived = document.getElementById('display-received');
        const displayChange = document.getElementById('display-change');
        const paymentButtons = document.querySelectorAll('.payment-btn');
        const clearPaymentButton = document.getElementById('clear-payment');
        const backspacePaymentButton = document.getElementById('backspace-payment');
        const completePaymentButton = document.getElementById('complete-payment');
        const productSearch = document.getElementById('product-search');
        const categoryButtons = document.querySelectorAll('.category-btn');

        updateOrderDisplay();
        updatePaymentDisplay();

        productItems.forEach(item => {
            item.addEventListener('click', function () {
                const product = {
                    id: this.dataset.id,
                    name: this.dataset.name,
                    price: parseFloat(this.dataset.price),
                    quantity: 1
                };
                addToOrder(product);
            });
        });

        paymentButtons.forEach(button => {
            button.addEventListener('click', function () {
                paymentInput += this.textContent;
                updatePaymentDisplay();

            });
        });

        clearPaymentButton.addEventListener('click', function () {
            paymentInput = '';
            paymentAmount = 0;
            updatePaymentDisplay();
        });

        if (backspacePaymentButton) {
            backspacePaymentButton.addEventListener('click', function () {
                paymentInput = paymentInput.slice(0, -1);
                updatePaymentDisplay();
            });
        }

        completePaymentButton.addEventListener('click', function () {
            const enteredAmount = parseFloat(paymentInput || '0');
            const total = calculateTotal();
            const totalPayment = paymentAmount + enteredAmount;
            const change = totalPayment - total;

            if (totalPayment >= total) {
                paymentAmount += enteredAmount;
                paymentInput = '';
                alert(`Payment completed! Change: PHP ${change.toFixed(2)}`);
                currentOrder = [];
                paymentAmount = 0;

        //         const orderData = {
        //     total_amount: total,
        //     amount_received: totalPayment,
        //     change: change,
        //     items: currentOrder.map(item => ({
        //         product_id: item.id,
        //         quantity: item.quantity,
        //         unit_price: item.price,
        //         total_price: item.price * item.quantity
        //     }))
        // };

        //  fetch('/api/orders', {
        //     method: 'POST',
        //     headers: {
        //         'Content-Type': 'application/json',
        //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        //     },
        //     body: JSON.stringify(orderData)
        // })
        // .then(response => response.json())
        // .then(data => {
        //     alert(`Order #${data.id} completed! Change: PHP ${change.toFixed(2)}`);
        //     currentOrder = [];
        //     paymentAmount = 0;
        //     paymentInput = '';
        //     updateOrderDisplay();
        //     updatePaymentDisplay();
        // })
        // .catch(error => {
        //     console.error('Error:', error);
        //     alert('Error saving order');
        // });
                updateOrderDisplay();
                updatePaymentDisplay();
            } else {
                alert(`Insufficient payment! Need PHP ${(total - totalPayment).toFixed(2)} more.`);
            }
        });

        productSearch.addEventListener('input', function () {
            filterProducts(this.value.toLowerCase());
        });

        categoryButtons.forEach(button => {
            button.addEventListener('click', function () {
                filterProducts('', this.dataset.category);
            });
        });

        function addToOrder(product) {
            const existingItem = currentOrder.find(item => item.id === product.id);
            if (existingItem) existingItem.quantity += 1;
            else currentOrder.push({ ...product });
            updateOrderDisplay();
        }

        function updateOrderDisplay() {
            orderItemsTable.innerHTML = '';
            currentOrder.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="p-2">
                        <div class="flex items-center">
                            <button class="px-2 py-1 bg-gray-200 rounded decrease-qty" data-index="${index}">-</button>
                            <span class="mx-2">${item.quantity}</span>
                            <button class="px-2 py-1 bg-gray-200 rounded increase-qty" data-index="${index}">+</button>
                        </div>
                    </td>
                    <td class="p-2">${item.name}</td>
                     <td class="p-2">PHP ${item.price.toFixed(2)}</td>
                    <td class="p-2">PHP ${(item.price * item.quantity).toFixed(2)}</td>
                    <td class="p-2 bg-red-500">
                        <button class="px-2 py-1 bg-red-500 text-white rounded text-sm remove-item" data-index="${index}">Remove</button>
                    </td>
                `;
                orderItemsTable.appendChild(row);
            });

            document.querySelectorAll('.decrease-qty').forEach(btn => {
                btn.onclick = () => {
                    const i = +btn.dataset.index;
                    if (currentOrder[i].quantity > 1) currentOrder[i].quantity--;
                    updateOrderDisplay();
                };
            });

            document.querySelectorAll('.increase-qty').forEach(btn => {
                btn.onclick = () => {
                    currentOrder[+btn.dataset.index].quantity++;
                    updateOrderDisplay();
                };
            });

            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.onclick = () => {
                    currentOrder.splice(+btn.dataset.index, 1);
                    updateOrderDisplay();
                };
            });

            const total = calculateTotal();
            totalAmountElement.textContent = `PHP ${total.toFixed(2)}`;
            displayTotal.textContent = `PHP ${total.toFixed(2)}`;

            updatePaymentDisplay();
        }

        function updatePaymentDisplay() {
            const currentInput = paymentInput ? parseFloat(paymentInput) : 0;
            const totalPayment = paymentAmount + currentInput;
            const total = calculateTotal();
            const change = totalPayment - total;

            displayReceived.textContent = `PHP ${totalPayment.toFixed(2)}`;
            displayChange.textContent = `PHP ${change > 0 ? change.toFixed(2) : '0.00'}`;

            // Disable complete payment button if:
            // 1. There are no items in the order, OR
            // 2. The payment is not enough to cover the total
            const hasOrder = currentOrder.length > 0;
            const isPaymentSufficient = totalPayment >= total;
            const isValidPayment = !isNaN(currentInput); // Check if the input is a valid number

            completePaymentButton.disabled = !hasOrder || !isValidPayment || !isPaymentSufficient;

            // Visual feedback for disabled state
            if (completePaymentButton.disabled) {
                completePaymentButton.classList.add('opacity-50', 'cursor-not-allowed');
                completePaymentButton.classList.remove('opacity-100');
            } else {
                completePaymentButton.classList.remove('opacity-50', 'cursor-not-allowed');
                completePaymentButton.classList.add('opacity-100');
            }
        }

        function calculateTotal() {
            return currentOrder.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        }

        function filterProducts(searchTerm = '', category = 'all') {
            productItems.forEach(item => {
                const name = item.dataset.name.toLowerCase();
                const itemCategory = item.dataset.category;
                const matchesSearch = searchTerm ? name.includes(searchTerm) : true;
                const matchesCategory = category === 'all' || itemCategory === category;
                item.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
            });
        }
    </script>
</x-filament::page>