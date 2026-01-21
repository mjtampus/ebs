// // Initialize with Livewire data
// document.addEventListener("livewire:init", () => {
//     // Wait for cashier data to be loaded
//     Livewire.on("cashierDataLoaded", (cashierData) => {
//         // Main POS variables
//         window.cashierData = cashierData;
//         console.log("cashierData", cashierData);
//         let currentOrder = [];
//         let paymentAmount = 0;
//         let paymentInput = "";

//         // DOM elements
//         const productItems = document.querySelectorAll(".product-item");
//         const orderItemsTable = document.getElementById("order-items");
//         const totalAmountElement = document.getElementById("total-amount");
//         const displayTotal = document.getElementById("display-total");
//         const displayReceived = document.getElementById("display-received");
//         const displayChange = document.getElementById("display-change");
//         const paymentButtons = document.querySelectorAll(".payment-btn");
//         const clearPaymentButton = document.getElementById("clear-payment");
//         const backspacePaymentButton =
//             document.getElementById("backspace-payment");
//         const completePaymentButton =
//             document.getElementById("complete-payment");
//         const productSearch = document.getElementById("product-search");
//         const categoryButtons = document.querySelectorAll(".category-btn");

//         // Initialize display
//         updateOrderDisplay();
//         updatePaymentDisplay();

//         // ============ KEYBOARD EVENT LISTENERS ============
//         document.addEventListener("keydown", function (event) {
//             // Only handle keyboard input when not typing in search box
//             if (document.activeElement === productSearch) return;

//             const key = event.key;

//             // Handle numbers (0-9)
//             if (/^[0-9]$/.test(key)) {
//                 event.preventDefault();
//                 paymentInput += key;
//                 updatePaymentDisplay();
//                 return;
//             }

//             // Handle decimal point
//             if (key === "." && !paymentInput.includes(".")) {
//                 event.preventDefault();
//                 paymentInput += ".";
//                 updatePaymentDisplay();
//                 return;
//             }

//             // Handle Backspace
//             if (key === "Backspace") {
//                 event.preventDefault();
//                 paymentInput = paymentInput.slice(0, -1);
//                 updatePaymentDisplay();
//                 return;
//             }

//             // Handle Delete or Clear (Delete key or Escape)
//             if (key === "Delete" || key === "Escape") {
//                 event.preventDefault();
//                 paymentInput = "";
//                 paymentAmount = 0;
//                 updatePaymentDisplay();
//                 return;
//             }

//             // Handle Enter to complete payment
//             if (key === "Enter") {
//                 event.preventDefault();
//                 if (!completePaymentButton.disabled) {
//                     processPayment();
//                 }
//                 return;
//             }

//             // Handle F1-F9 for quick product selection (first 9 products)
//             if (event.key.startsWith("F") && event.key.length === 2) {
//                 const fNumber = parseInt(event.key.substring(1));
//                 if (fNumber >= 1 && fNumber <= 9) {
//                     event.preventDefault();
//                     const productIndex = fNumber - 1;
//                     if (productItems[productIndex]) {
//                         productItems[productIndex].click();
//                     }
//                 }
//                 return;
//             }

//             // Handle Ctrl+Z for removing last item from order
//             if (event.ctrlKey && key === "z") {
//                 event.preventDefault();
//                 if (currentOrder.length > 0) {
//                     currentOrder.pop();
//                     updateOrderDisplay();
//                 }
//                 return;
//             }

//             // Handle Ctrl+A for clearing entire order
//             if (event.ctrlKey && key === "a") {
//                 event.preventDefault();
//                 if (currentOrder.length > 0 && confirm("Clear entire order?")) {
//                     currentOrder = [];
//                     updateOrderDisplay();
//                 }
//                 return;
//             }
//         });

//         // Prevent search box from losing focus when using keyboard shortcuts
//         productSearch.addEventListener("blur", function () {
//             // Small delay to allow keyboard shortcuts to work
//             setTimeout(() => {
//                 if (
//                     !document.activeElement ||
//                     document.activeElement === document.body
//                 ) {
//                     // Don't auto-focus if user clicked somewhere else intentionally
//                 }
//             }, 100);
//         });

//         // ============ EXISTING EVENT LISTENERS ============
//         productItems.forEach((item, index) => {
//             item.addEventListener("click", function () {
//                 const product = {
//                     id: this.dataset.id,
//                     name: this.dataset.name,
//                     price: parseFloat(this.dataset.price),
//                     stock: parseInt(this.dataset.stock),
//                     quantity: 1,
//                 };
//                 addToOrder(product);
//             });

//             // Add F-key hint to first 9 products
//             if (index < 9) {
//                 const fKeyHint = document.createElement("div");
//                 fKeyHint.className =
//                     "absolute top-1 right-1 bg-blue-500 text-white text-xs px-1 rounded";
//                 fKeyHint.textContent = `F${index + 1}`;
//                 fKeyHint.style.fontSize = "10px";
//                 item.style.position = "relative";
//                 item.appendChild(fKeyHint);
//             }
//         });

//         paymentButtons.forEach((button) => {
//             button.addEventListener("click", function () {
//                 paymentInput += this.textContent;
//                 updatePaymentDisplay();
//             });
//         });

//         clearPaymentButton.addEventListener("click", function () {
//             paymentInput = "";
//             paymentAmount = 0;
//             updatePaymentDisplay();
//         });

//         if (backspacePaymentButton) {
//             backspacePaymentButton.addEventListener("click", function () {
//                 paymentInput = paymentInput.slice(0, -1);
//                 updatePaymentDisplay();
//             });
//         }

//         completePaymentButton.addEventListener("click", processPayment);

//         productSearch.addEventListener("input", function () {
//             filterProducts(this.value.toLowerCase());
//         });

//         categoryButtons.forEach((button) => {
//             button.addEventListener("click", function () {
//                 filterProducts("", this.dataset.categoryId);
//             });
//         });

//         // Core functions
//         function addToOrder(product) {
//             const existingItem = currentOrder.find(
//                 (item) => item.id === product.id
//             );
//             if (existingItem) existingItem.quantity += 1;
//             else currentOrder.push({ ...product });
//             updateOrderDisplay();
//         }

//         function processPayment() {
//             const enteredAmount = parseFloat(paymentInput || "0");
//             const total = calculateTotal();
//             const totalPayment = paymentAmount + enteredAmount;
//             const change = totalPayment - total;

//             if (totalPayment >= total) {
//                 const orderData = {
//                     cashier_id: cashierData[0].id,
//                     transaction_code: "TRX-" + Date.now(),
//                     total_amount: total,
//                     amount_received: totalPayment,
//                     change: change,
//                     items: currentOrder.map((item) => ({
//                         product_id: item.id,
//                         quantity: item.quantity,
//                         unit_price: item.price,
//                         total_price: item.price * item.quantity,
//                     })),
//                 };
//                 console.log("orderData", orderData);
//                 paymentAmount += enteredAmount;
//                 paymentInput = "";

//                 fetch("/api/transactions", {
//                     method: "POST",
//                     headers: {
//                         "Content-Type": "application/json",
//                         Accept: "application/json",
//                         "X-CSRF-TOKEN": document.querySelector(
//                             'meta[name="csrf-token"]'
//                         ).content,
//                     },
//                     body: JSON.stringify(orderData),
//                 })
//                     .then(handleResponse)
//                     .then((data) => {
//                         showSuccess(data, change);
//                         resetOrder();
//                     })
//                     .catch(handleError);
//             } else {
//                 alert(
//                     `Insufficient payment! Need PHP ${(
//                         total - totalPayment
//                     ).toFixed(2)} more.`
//                 );
//             }
//         }

//         function handleResponse(response) {
//             if (!response.ok) {
//                 return response.text().then((text) => {
//                     throw new Error(text);
//                 });
//             }
//             return response.json();
//         }

//         function showSuccess(data, change) {
//             alert(
//                 `Order #${data.id} completed! Change: PHP ${change.toFixed(2)}`
//             );
//         }

//         function resetOrder() {
//             currentOrder = [];
//             paymentAmount = 0;
//             paymentInput = "";
//             updateOrderDisplay();
//             updatePaymentDisplay();
//         }

//         function handleError(error) {
//             console.error("Error:", error);
//             alert(error.message || "Error saving order");
//         }

//         function updateOrderDisplay() {
//             orderItemsTable.innerHTML = "";
//             currentOrder.forEach((item, index) => {
//                 const row = document.createElement("tr");
//                 row.innerHTML = `
//                     <td class="p-2">
//                         <div class="flex items-center">
//                             <button class="px-2 py-1 bg-gray-200 rounded decrease-qty" data-index="${index}">-</button>
//                             <span class="mx-2">${item.quantity}</span>
//                             <button class="px-2 py-1 bg-gray-200 rounded increase-qty" data-index="${index}">+</button>
//                         </div>
//                     </td>
//                     <td class="p-2">${item.name}</td>
//                     <td class="p-2">PHP ${item.price.toFixed(2)}</td>
//                     <td class="p-2">PHP ${(item.price * item.quantity).toFixed(
//                         2
//                     )}</td>
//                     <td class="p-2 bg-red-500">
//                         <button class="px-2 py-1 bg-red-500 text-white rounded text-sm remove-item" data-index="${index}">Remove</button>
//                     </td>
//                 `;
//                 orderItemsTable.appendChild(row);
//             });

//             // Add event listeners for quantity controls
//             document.querySelectorAll(".decrease-qty").forEach((btn) => {
//                 btn.onclick = () => {
//                     const i = +btn.dataset.index;
//                     if (currentOrder[i].quantity > 1)
//                         currentOrder[i].quantity--;
//                     updateOrderDisplay();
//                 };
//             });

//             document.querySelectorAll(".increase-qty").forEach((btn) => {
//                 btn.onclick = () => {
//                     const i = +btn.dataset.index;
//                     const item = currentOrder[i];

//                     if (item.quantity < item.stock) {
//                         item.quantity++;
//                         updateOrderDisplay();
//                     } else {
//                         alert(
//                             `Cannot increase quantity. Only ${item.stock} ${item.name} available in stock.`
//                         );
//                     }
//                 };
//             });

//             document.querySelectorAll(".remove-item").forEach((btn) => {
//                 btn.onclick = () => {
//                     currentOrder.splice(+btn.dataset.index, 1);
//                     updateOrderDisplay();
//                 };
//             });

//             const total = calculateTotal();
//             totalAmountElement.textContent = `PHP ${total.toFixed(2)}`;
//             displayTotal.textContent = `PHP ${total.toFixed(2)}`;

//             updatePaymentDisplay();
//         }

//         function updatePaymentDisplay() {
//             const currentInput = paymentInput ? parseFloat(paymentInput) : 0;
//             const totalPayment = paymentAmount + currentInput;
//             const total = calculateTotal();
//             const change = totalPayment - total;

//             displayReceived.textContent = `PHP ${totalPayment.toFixed(2)}`;
//             displayChange.textContent = `PHP ${
//                 change > 0 ? change.toFixed(2) : "0.00"
//             }`;

//             // Update payment button state
//             const hasOrder = currentOrder.length > 0;
//             const isPaymentSufficient = totalPayment >= total;
//             const isValidPayment = !isNaN(currentInput);

//             completePaymentButton.disabled =
//                 !hasOrder || !isValidPayment || !isPaymentSufficient;

//             // Visual feedback
//             if (completePaymentButton.disabled) {
//                 completePaymentButton.classList.add(
//                     "opacity-50",
//                     "cursor-not-allowed"
//                 );
//                 completePaymentButton.classList.remove("opacity-100");
//             } else {
//                 completePaymentButton.classList.remove(
//                     "opacity-50",
//                     "cursor-not-allowed"
//                 );
//                 completePaymentButton.classList.add("opacity-100");
//             }

//             // Visual feedback for current payment input
//             if (paymentInput) {
//                 displayReceived.parentElement.classList.add("bg-yellow-500");
//             } else {
//                 displayReceived.parentElement.classList.remove("bg-yellow-500");
//             }
//         }

//         function calculateTotal() {
//             return currentOrder.reduce(
//                 (sum, item) => sum + item.price * item.quantity,
//                 0
//             );
//         }

//         function filterProducts(searchTerm = "", category = "all") {
//             productItems.forEach((item) => {
//                 const name = item.dataset.name.toLowerCase();
//                 const itemCategory = item.dataset.categoryId;

//                 const matchesSearch = searchTerm
//                     ? name.includes(searchTerm)
//                     : true;
//                 const matchesCategory =
//                     category === "all" || itemCategory === category;
//                 item.style.display =
//                     matchesSearch && matchesCategory ? "block" : "none";
//             });
//         }

//         // ============ SHOW KEYBOARD SHORTCUTS HELP ============
//         function showKeyboardHelp() {
//             alert(`Keyboard Shortcuts:
            
// PAYMENT:
// • 0-9: Enter payment amount
// • . (dot): Add decimal point
// • Backspace: Delete last digit
// • Delete/Escape: Clear payment
// • Enter: Complete payment

// PRODUCTS:
// • F1-F9: Add first 9 products to order

// ORDER MANAGEMENT:
// • Ctrl+Z: Remove last item from order
// • Ctrl+A: Clear entire order (with confirmation)

// SEARCH:
// • Click search box to search products normally`);
//         }

//         // Add help button (you can add this to your HTML)
//         // <button onclick="showKeyboardHelp()" class="text-xs text-blue-500">Show Keyboard Shortcuts</button>
//         window.showKeyboardHelp = showKeyboardHelp;

//         // Show help on page load (optional)
//         console.log(
//             "💡 Keyboard shortcuts enabled! Press Ctrl+H or check console for help."
//         );

//         // Optional: Add Ctrl+H for help
//         document.addEventListener("keydown", function (event) {
//             if (event.ctrlKey && event.key === "h") {
//                 event.preventDefault();
//                 showKeyboardHelp();
//             }
//         });
//     });
// });