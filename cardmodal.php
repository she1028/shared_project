<?php
// cardmodal.php - Food detail modal (included by foodmenu.php)
// Guests can open the modal, but add_to_cart.php blocks unauthenticated adds.
// If accessed directly, redirect back to Food Menu.
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'cardmodal.php') {
    header('Location: foodmenu.php');
    exit;
}
?>

<style>
    /* .custom-modal {
        max-width: 1000px;
        width: fit-content;

    } */
    .custom-modal .modal-content {
        height: auto;
    }

    .back-action {
        cursor: pointer;
        user-select: none;
        width: fit-content;
    }

    .qty-box {
        display: flex;
        align-items: center;
        border: 1px solid #ccc;
        border-radius: 6px;
        overflow: hidden;
    }

    .qty-box button {
        background: none;
        border: none;
        padding: 6px 12px;
        cursor: pointer;
    }

    .qty-box span {
        padding: 0 12px;
        font-size: 14px;
    }

    .color-dot {
        width: 16px;
        height: 16px;
        border-radius: 2px;
        display: inline-block;
    }

    /* Toast styling (match rentals) */
    #cartToastContainer {
        position: fixed;
        bottom: 1rem;
        right: 1rem;
        z-index: 1080;
    }
</style>

<!-- Food detail modal -->
<div class="modal fade" tabindex="-1" id="foodModal">
    <div class="modal-dialog modal-dialog-centered modal-lg custom-modal">
        <div class="modal-content p-3" style="background-color: #ede3d4;">
            <div class="modal-body">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-5 col-12 align-items-center">
                        <img id="modalFoodImage" class="img-fluid">
                    </div>
                    <!-- Back button -->
                    <div class="col-lg-7 col-12 p-2 mt-2">
                        <div class="m-2">
                            <span class="d-inline-flex align-items-center back-action g-2" data-bs-dismiss="modal">
                                <i class="material-icons">&#xe5c4;</i>
                                <span>back</span>
                            </span>
                        </div>
                        <!-- Category -->
                        <div class="d-flex align-items-center justify-content-center m-2">
                            <span id="modalFoodCategory"
                                class="rounded-5 text-center py-1 px-3"
                                style="background-color: #c6c6c6cc; font-size: 13px;">
                            </span>
                        </div>
                        <div class="row mt-2">
                            <!-- Title -->
                            <div id="modalFoodName" class="h3 fw-bold"></div>
                            <!-- details -->
                            <div class="details mt-2">
                                <h5>Details:</h5>
                                <p id="modalFoodServing" class="mb-0"></p>
                                <p id="modalFoodExtra" class="mb-0"></p>

                            </div>
                            <!-- description -->
                            <div class="description mt-3">
                                <h5>Description:</h5>
                                <p id="modalFoodDescription" style="text-align: justify;"></p>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row align-items-center">
                            <!-- price -->
                            <div class="col-lg-6 col-12">
                                <h4 class="fw-semibold">Price: <span id="modalFoodPrice"></span></h4>
                            </div>
                            <!-- availabile colors -->
                            <!-- <div class="col-lg-6 col-12">
                                <span class="fs-6">Available In: </span>
                                <span class="color-dot bg-danger"></span>
                                <span class="color-dot bg-primary"></span>
                                <span class="color-dot bg-dark"></span>
                            </div> -->
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <!-- quantity  -->
                            <div class="qty-box">
                                <button type="button" id="qty-minus">−</button>
                                <span>1</span>
                                <button type="button" id="qty-plus">+</button>
                            </div>
                            <!-- add to cart -->
                            <div class="d-flex align-items-center">
                                <span id="addToCartBtn" class="btn rounded-2 text-center py-1 px-2 ms-5" style="background-color: #c6c6c6cc; justify-content: center;">add to cart</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast container (match rentals) -->
<div id="cartToastContainer">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="cartToastMessage">
                Item added to cart!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    (() => {
        let currentFood = null;
        let currentQty = 1;
        const qtyDisplay = document.querySelector('#foodModal .qty-box span');

        function showToast(message, success = true) {
            const toastEl = document.getElementById('cartToast');
            const toastMessage = document.getElementById('cartToastMessage');
            toastMessage.textContent = message;

            toastEl.classList.toggle('text-bg-success', success);
            toastEl.classList.toggle('text-bg-danger', !success);

            const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
            toast.show();
        }

        window.openFoodModal = function (item) {
            currentFood = item || null;
            currentQty = 1;

            document.getElementById('modalFoodImage').src = item.image || '';
            document.getElementById('modalFoodName').innerText = item.name || '';
            document.getElementById('modalFoodDescription').innerText = item.description || '';
            document.getElementById('modalFoodPrice').innerText = '₱ ' + Number(item.price || 0).toFixed(2);
            document.getElementById('modalFoodServing').innerText = item.serving || '';
            document.getElementById('modalFoodCategory').innerText = item.category || '';

            if (qtyDisplay) qtyDisplay.innerText = currentQty;

            const modal = new bootstrap.Modal(document.getElementById('foodModal'));
            modal.show();
        };

        document.getElementById('qty-minus').addEventListener('click', () => {
            if (currentQty > 1) currentQty--;
            if (qtyDisplay) qtyDisplay.innerText = currentQty;
        });

        document.getElementById('qty-plus').addEventListener('click', () => {
            currentQty++;
            if (qtyDisplay) qtyDisplay.innerText = currentQty;
        });

        document.getElementById('addToCartBtn').addEventListener('click', () => {
            if (!currentFood) return;

            const postData = {
                food_id: currentFood.food_id || currentFood.id,
                id: currentFood.id,
                name: currentFood.name,
                price: Number(currentFood.price),
                qty: Number(currentQty),
                image: currentFood.image,
                category: currentFood.category,
                serving: currentFood.serving || '',
                type: 'food'
            };

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(postData)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, true);
                        if (typeof updateCartCount === 'function') {
                            updateCartCount();
                        }
                        const modalEl = document.getElementById('foodModal');
                        const instance = bootstrap.Modal.getInstance(modalEl);
                        if (instance) instance.hide();
                    } else {
                        showToast(data.message || 'Error adding to cart', false);
                    }
                })
                .catch(() => showToast('Error adding to cart', false));
        });
    })();
</script>

<?php return; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="checkout.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>
    <!-- HERO / LOGO -->
    <section class="hero-section">
        <div class="mx-4">
            <span class="d-inline-flex align-items-center back-action g-2" onclick="history.back()">
                <i class="material-icons">&#xe5c4;</i>
                <span>back</span>
            </span>
        </div>
        <div class="text-center">
            <img src="images/YMZM-logo.png" class="logo mb-2">
            <h5 class="fw-bold m-0">YMZM</h5>
        </div>
    </section>

    <div class="container my-5">
        <div class="row g-3 justify-content-center">

            <!-- LEFT: Checkout Form -->
            <div class="col-lg-6 col-md-7">
                <div class="checkout-box">
                    <h6 class="fw-bold mb-3">CONTACT INFORMATION</h6>

                    <!-- Full Name -->
                    <div class="mb-2">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" placeholder="Full Name">
                        <small class="small-msg"></small>
                    </div>

                    <!-- Contact Number -->
                    <div class="mb-2">
                        <label class="form-label">Contact No.</label>
                        <input type="text" class="form-control" placeholder="Contact No." value="+63">
                        <small class="small-msg"></small>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" placeholder="Email">
                        <small class="small-msg"></small>
                    </div>

                    <div class="mb-2">
                        <select class="form-select" id="paymentMethod">
                            <option value="" selected disabled>Payment Method</option>
                            <option value="full">Full Payment</option>
                            <option value="cash">Cash</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>

                    <!-- Delivery & Event Details -->
                    <div class="mb-2">
                        <select class="form-select" id="deliveryMethod">
                            <option value="" selected disabled>Delivery Method</option>
                            <option value="ship">Ship</option>
                        </select>
                    </div>

                    <!-- Ship Fields -->
                    <div id="shipFields" style="display:none;">
                        <div class="mb-2">
                            <input type="text" class="form-control" placeholder="Venue">
                        </div>
                        <div class="mb-2">
                            <select class="form-select">
                                <option value="" selected disabled>Venue Type</option>
                                <option>Indoor</option>
                                <option>Outdoor</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control" placeholder="Country" value="Philippines" readonly>
                        </div>
                        <!-- Address -->
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Street Number / #">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Barangay">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="City">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Province" value="Batangas"
                                    readonly>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Postal Code">
                            </div>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="3"
                                placeholder="Notes or instructions for the seller"></textarea>
                            <small class="notes-msg" style="display:block;margin-top:5px;"></small>
                        </div>
                    </div>

                    <!-- Pick Up Field -->
                    <div id="pickupField" style="display:none;">
                        <input type="text" class="form-control" value="067 Bawi Padre Garcia Batangas" readonly>
                    </div>
                    <div class="checkout-box">
                        <form id="checkoutForm">
                            <!-- All your input fields go here -->
                            <!-- Error message -->
                            <div id="checkoutError" style="color:red; margin-bottom:10px; display:none;"></div>

                            <button type="submit" class="btn btn-secondary w-100 mt-3">Complete Order</button>

                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Order Summary -->
            <div class="col-lg-4 col-md-5">
                <div class="order-summary">
                    <h6 class="fw-bold mb-3">ORDER SUMMARY</h6>
                    <?php
                    $subtotal = 0;
                    foreach ($cart as $index => $item):
                        $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
                        $lineTotal = ((float)$item['price']) * $qty;
                        $subtotal += $lineTotal;
                        ?>
                        <div class="d-flex mb-3 align-items-center">
                            <div class="item-number"><?= $index + 1 ?></div>
                            <div class="flex-grow-1 ms-2">
                                <div class="fw-semibold"><?= $item['name'] ?></div>
                                <small class="text-muted">Qty: <?= $qty ?></small>
                            </div>
                            <div>₱<?= number_format($lineTotal, 2) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <hr>
                    <?php
                    $shipping = 120;
                    $total = $subtotal + $shipping;
                    ?>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal</span>
                        <span>₱<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Shipping</span>
                        <span>₱<?= number_format($shipping, 2) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>TOTAL</span>
                        <span>₱<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Cash Payment Modal -->
    <div class="modal fade" id="cashModal" tabindex="-1" aria-labelledby="cashModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="cashModalLabel">Thank You for Your Order!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body text-center">
                    <p>Make sure to prepare the payment below when your order is delivered:</p>
                    <h4>Total Amount: ₱<span id="cashTotal"></span></h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            // --- DELIVERY METHOD TOGGLE ---
            const deliverySelect = document.getElementById("deliveryMethod");
            const shipFields = document.getElementById("shipFields");
            const pickupField = document.getElementById("pickupField");

            const toggleDeliveryFields = () => {
                if (deliverySelect.value === "ship") {
                    shipFields.style.display = "block";
                    pickupField.style.display = "none";
                } else if (deliverySelect.value === "pickup") {
                    shipFields.style.display = "none";
                    pickupField.style.display = "block";
                }
            };

            // Default to ship so required address fields are visible
            if (!deliverySelect.value) {
                deliverySelect.value = "ship";
            }
            toggleDeliveryFields();

            deliverySelect.addEventListener("change", toggleDeliveryFields);

            // --- Helper to create message ---
            function createMsg(input) {
                const msg = document.createElement("small");
                msg.style.display = "block";
                msg.style.marginTop = "5px";
                input.parentNode.appendChild(msg);
                return msg;
            }

            // --- CASH MODAL REDIRECT ---
            const cashModalCloseBtn = document.querySelector("#cashModal .btn-close");
            if (cashModalCloseBtn) {
                cashModalCloseBtn.addEventListener("click", () => {
                    window.location.href = "index.php";
                });
            }
            const cashModalFooterBtn = document.querySelector("#cashModal .modal-footer button");
            if (cashModalFooterBtn) {
                cashModalFooterBtn.addEventListener("click", () => {
                    window.location.href = "index.php";
                });
            }

            // --- FULL NAME VALIDATION ---
            const nameInput = document.querySelector('input[placeholder="Full Name"]');
            const nameMsg = createMsg(nameInput);

            nameInput.addEventListener("input", () => {
                const parts = nameInput.value.trim().split(/\s+/);
                if (parts.length < 2) {
                    nameMsg.textContent = "Enter at least First and Last Name";
                    nameMsg.style.color = "red";
                    nameInput.style.borderColor = "red";
                    return;
                }
                const first = parts[0];
                const last = parts[parts.length - 1];
                const middle = parts.length > 2 ? parts.slice(1, -1).map(n => n[0].toUpperCase() + ".").join(" ") : "";
                const standardized = [first, middle, last].filter(Boolean).join(" ");
                nameMsg.textContent = "Valid Name: " + standardized;
                nameMsg.style.color = "green";
                nameInput.style.borderColor = "green";
            });

            // --- CONTACT VALIDATION ---
            const contactInput = document.querySelector('input[placeholder="Contact No."]');
            contactInput.value = "09";
            const contactMsg = createMsg(contactInput);

            function updateContact() {
                let val = contactInput.value.replace(/\D/g, "");
                if (val.startsWith("09")) {
                    val = val.slice(0, 10);
                    contactInput.value = val;
                    if (val.length === 10) {
                        contactMsg.textContent = "Valid Contact";
                        contactMsg.style.color = "green";
                        contactInput.style.borderColor = "green";
                    } else {
                        contactMsg.textContent = "Contact must be 10 digits starting with 09";
                        contactMsg.style.color = "red";
                        contactInput.style.borderColor = "red";
                    }
                } else {
                    val = val.slice(0, 12);
                    contactInput.value = "+63" + val;
                    if (val.length === 12) {
                        contactMsg.textContent = "Valid Contact";
                        contactMsg.style.color = "green";
                        contactInput.style.borderColor = "green";
                    } else {
                        contactMsg.textContent = "Contact must be 12 digits starting with +63";
                        contactMsg.style.color = "red";
                        contactInput.style.borderColor = "red";
                    }
                }
            }

            contactInput.addEventListener("input", updateContact);
            contactInput.addEventListener("keydown", (e) => {
                if ((e.key === "Backspace" || e.key === "Delete") && contactInput.selectionStart <= 2 && contactInput.value.startsWith("09")) e.preventDefault();
                if ((e.key === "Backspace" || e.key === "Delete") && contactInput.selectionStart <= 3 && contactInput.value.startsWith("+63")) e.preventDefault();
                if (contactInput.selectionStart < 2 && contactInput.value.startsWith("09") && e.key.length === 1) e.preventDefault();
                if (contactInput.selectionStart < 3 && contactInput.value.startsWith("+63") && e.key.length === 1) e.preventDefault();
            });
            contactInput.addEventListener("paste", (e) => {
                e.preventDefault();
                let paste = e.clipboardData.getData("text").replace(/\D/g, "");
                if (paste.startsWith("09")) {
                    paste = paste.slice(0, 10);
                    contactInput.value = paste;
                } else {
                    paste = paste.slice(0, 10);
                    contactInput.value = "+63" + paste;
                }
                updateContact();
            });

            // --- EMAIL VALIDATION ---
            const emailInput = document.querySelector('input[type="email"]');
            const emailMsg = createMsg(emailInput);

            emailInput.addEventListener("input", () => {
                const email = emailInput.value.trim();
                const gmailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
                if (!gmailRegex.test(email)) {
                    emailMsg.textContent = "Enter a valid Gmail address";
                    emailMsg.style.color = "red";
                    emailInput.style.borderColor = "red";
                } else {
                    emailMsg.textContent = "Valid Gmail";
                    emailMsg.style.color = "green";
                    emailInput.style.borderColor = "green";
                }
            });

            // --- ADDRESS VALIDATION (Ship) ---
            const streetInput = document.querySelector('input[placeholder="Street Number / #"]');
            const barangayInput = document.querySelector('input[placeholder="Barangay"]');
            const cityInput = document.querySelector('input[placeholder="City"]');
            const provinceInput = document.querySelector('input[placeholder="Province"]');
            const postalInput = document.querySelector('input[placeholder="Postal Code"]');
            const notesInput = document.querySelector('textarea[placeholder="Notes or instructions for the seller"]');

            const streetMsg = createMsg(streetInput);
            const barangayMsg = createMsg(barangayInput);
            const cityMsg = createMsg(cityInput);
            const provinceMsg = createMsg(provinceInput);
            const postalMsg = createMsg(postalInput);
            const notesMsg = createMsg(notesInput);

            function validateShipFields() {
                if (deliverySelect.value !== "ship") return;

                streetInput.value = streetInput.value.replace(/\D/g, "").slice(0, 4);
                streetMsg.textContent = streetInput.value ? "Valid street number" : "Street number is required";
                streetMsg.style.color = streetInput.value ? "green" : "red";
                streetInput.style.borderColor = streetInput.value ? "green" : "red";

                barangayInput.value = barangayInput.value.replace(/[^a-zA-Z\s]/g, "").slice(0, 20);
                barangayMsg.textContent = barangayInput.value ? "Valid barangay" : "Barangay is required";
                barangayMsg.style.color = barangayInput.value ? "green" : "red";
                barangayInput.style.borderColor = barangayInput.value ? "green" : "red";

                cityInput.value = cityInput.value.replace(/[^a-zA-Z\s]/g, "").slice(0, 20);
                cityMsg.textContent = cityInput.value ? "Valid city" : "City is required";
                cityMsg.style.color = cityInput.value ? "green" : "red";
                cityInput.style.borderColor = cityInput.value ? "green" : "red";

                provinceInput.value = "Batangas";
                provinceInput.readOnly = true;
                provinceMsg.textContent = "Province is Batangas";
                provinceMsg.style.color = "black";
                provinceInput.style.borderColor = "black";

                postalInput.value = postalInput.value.replace(/\D/g, "").slice(0, 4);
                postalMsg.textContent = postalInput.value.length === 4 ? "Valid postal code" : "Postal code must be 4 digits";
                postalMsg.style.color = postalInput.value.length === 4 ? "green" : "red";
                postalInput.style.borderColor = postalInput.value.length === 4 ? "green" : "red";

                const MAX_LETTERS = 100;
                let text = notesInput.value;
                if (text.length > MAX_LETTERS) {
                    text = text.slice(0, MAX_LETTERS);
                    notesInput.value = text;
                    notesMsg.textContent = `Notes limited to ${MAX_LETTERS} characters`;
                    notesMsg.style.color = "red";
                    notesInput.style.borderColor = "red";
                } else {
                    notesMsg.textContent = `Characters: ${text.length}/${MAX_LETTERS}`;
                    notesMsg.style.color = "green";
                    notesInput.style.borderColor = "green";
                }
            }

            [streetInput, barangayInput, cityInput, postalInput, notesInput].forEach(input => {
                input.addEventListener("input", validateShipFields);
            });

        });

        // --- Complete Order ---
        const checkoutForm = document.getElementById("checkoutForm");
        const deliverySelectForm = document.getElementById("deliveryMethod");
        const checkoutError = document.getElementById("checkoutError");
        const paymentSelect = document.getElementById("paymentMethod");
        const cashTotalSpan = document.getElementById("cashTotal");

        checkoutForm.addEventListener("submit", function (e) {
            e.preventDefault();

            checkoutError.style.display = "none";
            checkoutError.textContent = "";

            const name = document.querySelector('input[placeholder="Full Name"]').value.trim();
            const contact = document.querySelector('input[placeholder="Contact No."]').value.trim();
            const email = document.querySelector('input[placeholder="Email"]').value.trim();
            const delivery = deliverySelectForm.value;
            const payment = paymentSelect.value;

            if (!name || !contact || !email || !delivery || !payment) {
                checkoutError.textContent = "Please complete all required details";
                checkoutError.style.display = "block";
                return;
            }

            if (delivery === "ship") {
                const shipFields = [
                    'input[placeholder="Street Number / #"]',
                    'input[placeholder="Barangay"]',
                    'input[placeholder="City"]',
                    'input[placeholder="Postal Code"]'
                ];

                for (let selector of shipFields) {
                    const field = document.querySelector(selector);
                    if (!field || !field.value.trim()) {
                        checkoutError.textContent = "Please complete all shipping details";
                        checkoutError.style.display = "block";
                        return;
                    }
                }
            }

            const shipping = <?= $shipping ?>;
            const totalAmount = <?= $total ?>;

            // Remember email for notifications widget
            try {
                localStorage.setItem('notifEmail', email);
            } catch (err) {}

            // Persist order to backend
            fetch("admin/processcheckout.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    name,
                    contact,
                    email,
                    payment,
                    delivery,
                    street: document.querySelector('input[placeholder="Street Number / #"]').value,
                    barangay: document.querySelector('input[placeholder="Barangay"]').value,
                    city: document.querySelector('input[placeholder="City"]').value,
                    province: "Batangas",
                    postal: document.querySelector('input[placeholder="Postal Code"]').value,
                    notes: document.querySelector('textarea').value,
                    shipping: shipping,
                    total: totalAmount
                })
            })
            .then(res => res.text())
            .then(data => {
                const trimmed = (data || "").trim();
                if (trimmed === "success") {
                    if (payment === "cash") {
                        cashTotalSpan.textContent = totalAmount.toFixed(2);
                        const cashModal = new bootstrap.Modal(document.getElementById('cashModal'));
                        cashModal.show();
                    } else if (payment === "full") {
                        const confirmPay = confirm("Are you sure you want to pay?");
                        if (confirmPay) {
                            window.location.href = "payment.php";
                        }
                    } else if (payment === "paypal") {
                        alert("PayPal checkout coming soon. Order saved as pending.");
                    }
                } else {
                    const friendly = trimmed === "error:empty_cart"
                        ? "Your cart is empty. Please add items before checking out."
                        : trimmed === "error:not_logged_in"
                            ? "Please sign in to checkout."
                        : trimmed;

                    checkoutError.textContent = friendly || "There was a problem processing your order. Please try again.";
                    checkoutError.style.display = "block";
                }
            })
            .catch(() => {
                checkoutError.textContent = "Unable to submit order. Please check your connection.";
                checkoutError.style.display = "block";
            });
        });

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
