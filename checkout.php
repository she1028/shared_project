                <?php
                if (session_status() === PHP_SESSION_NONE) {
                    session_name('client_session');
                    session_start();
                }

                // Guests cannot checkout
                $isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
                if (!$isLoggedIn) {
                    $currentUri = $_SERVER['REQUEST_URI'] ?? 'checkout.php';
                    header('Location: auth.php?next=' . urlencode($currentUri));
                    exit;
                }

                // Admin accounts should not use client checkout
                if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                    exit;
                }

                // If coming from cart.php, persist selected items + updated quantities for this checkout.
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $cartAll = $_SESSION['cart'] ?? [];
                    $qtyMap = $_POST['qty'] ?? [];

                    // Persist selected event date (date-only)
                    $eventDate = trim((string)($_POST['event_date'] ?? ''));
                    if ($eventDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
                        $_SESSION['checkout_event_date'] = $eventDate;
                    } else {
                        unset($_SESSION['checkout_event_date']);
                    }

                    // Update quantities in session cart (best-effort)
                    if (is_array($qtyMap)) {
                        foreach ($qtyMap as $idx => $qtyRaw) {
                            $i = (int)$idx;
                            if (!isset($cartAll[$i])) {
                                continue;
                            }
                            $qty = (int)$qtyRaw;
                            if ($qty < 1) {
                                $qty = 1;
                            }
                            $cartAll[$i]['qty'] = $qty;
                        }
                    }

                    $selected = $_POST['selected'] ?? [];
                    $selected = is_array($selected) ? $selected : [$selected];
                    $selectedIdx = [];
                    foreach ($selected as $s) {
                        $i = (int)$s;
                        if ($i >= 0 && isset($cartAll[$i])) {
                            $selectedIdx[$i] = true;
                        }
                    }

                    if (empty($selectedIdx)) {
                        $_SESSION['cart'] = $cartAll;
                        header('Location: cart.php?error=select');
                        exit;
                    }

                    $checkoutCart = [];
                    foreach (array_keys($selectedIdx) as $i) {
                        $checkoutCart[] = $cartAll[$i];
                    }

                    $_SESSION['cart'] = $cartAll;
                    $_SESSION['checkout_cart'] = $checkoutCart;

                    // Redirect to avoid form resubmission
                    header('Location: checkout.php');
                    exit;
                }

                $cart = $_SESSION['checkout_cart'] ?? ($_SESSION['cart'] ?? []);
                $checkoutEventDate = (string)($_SESSION['checkout_event_date'] ?? '');
                $checkoutDeliveryTime = (string)($_SESSION['checkout_delivery_time'] ?? '');

                // Require SMS confirmation before placing an order (prevents bypassing smsbooking.php)
                $smsConfirmed = !empty($_SESSION['sms_confirmed'])
                    && !empty($_SESSION['sms_booking_ref'])
                    && !empty($_SESSION['sms_phone'])
                    && !empty($_SESSION['sms_confirmed_at'])
                    && (time() - (int)$_SESSION['sms_confirmed_at'] <= 15 * 60);

                if (!$smsConfirmed) {
                    header('Location: smsbooking.php');
                    exit;
                }
                ?>
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
                    <!-- HERO / LOGO (simple layout like earlier) -->
                    <section class="hero-section">
                        <div class="mx-4">
                            <a class="d-inline-flex align-items-center back-action g-2" href="cart.php">
                                <i class="material-icons">&#xe5c4;</i>
                                <span>back</span>
                            </a>
                        </div>
                        <div class="text-center">
                            <img src="images/YMZM-logo.png" class="logo mb-2">
                            <h5 class="fw-bold m-0">YMZM</h5>
                        </div>
                    </section>

                    <div class="container my-5">
                        <div class="row g-3 justify-content-center align-items-stretch">

                            <!-- LEFT: Checkout Form -->
                            <div class="col-lg-6 col-md-7 d-flex">
                                <form id="checkoutForm" class="checkout-box w-100 h-100">

                                    <h6 class="fw-bold mb-3">CONTACT INFORMATION</h6>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="firstName" placeholder="First Name" autocomplete="given-name" required>
                                                <small class="small-msg"></small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Middle Initial <span class="text-muted small">(optional)</span></label>
                                                <input type="text" class="form-control" id="middleInitial" placeholder="M" maxlength="1" autocomplete="additional-name">
                                                <small class="small-msg"></small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="lastName" placeholder="Last Name" autocomplete="family-name" required>
                                                <small class="small-msg"></small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Contact No.</label>
                                                <input type="text" class="form-control" id="contactNumber" placeholder="09XXXXXXXXX" value="09" inputmode="numeric">
                                                <small class="small-msg"></small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Email</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="emailLocal" placeholder="Email" autocomplete="username" inputmode="email">
                                                    <span class="input-group-text">@gmail.com</span>
                                                </div>
                                                <small class="small-msg"></small>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Payment Method</label>
                                            <select class="form-select" id="paymentMethod" required>
                                                <option value="" selected disabled>Payment Method</option>
                                                <option value="cash">Cash</option>
                                                <option value="paypal">PayPal</option>
                                            </select>
                                        </div>
                                    <div class="mb-2">
                                        <select class="form-select" id="deliveryMethod" required>
                                            <option value="" selected disabled>Delivery Method</option>
                                            <option value="ship">Ship</option>
                                        </select>
                                    </div>

                                    <div id="shipFields" style="display:none;">
                                        <h6 class="fw-bold mb-3">SHIPPING INFORMATION</h6>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label class="form-label">Venue</label>
                                                <input type="text" class="form-control" placeholder="Venue">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Venue Type</label>
                                                <select class="form-select">
                                                    <option value="" selected disabled>Venue Type</option>
                                                    <option>Indoor</option>
                                                    <option>Outdoor</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Country</label>
                                                <input type="text" class="form-control" placeholder="Country" value="Philippines" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Street Number / #</label>
                                                <input type="text" class="form-control" placeholder="Street Number / #">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Barangay</label>
                                                <select class="form-select" id="barangaySelect">
                                                    <option value="" selected disabled>Select Barangay</option>
                                                    <option value="Bawi">Bawi</option>
                                                    <option value="Banaba">Banaba</option>
                                                    <option value="Castillo">Castillo</option>
                                                    <option value="Cawongan">Cawongan</option>
                                                    <option value="Manggas">Manggas</option>
                                                    <option value="Maugat">Maugat</option>
                                                    <option value="Payapa">Payapa</option>
                                                    <option value="Quilo-quilo">Quilo-quilo</option>
                                                    <option value="San Felipe">San Felipe</option>
                                                    <option value="San Miguel">San Miguel</option>
                                                    <option value="San Vicente">San Vicente</option>
                                                    <option value="Taug">Taug</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Municipality</label>
                                                <select class="form-select" id="municipalitySelect">
                                                    <option value="" selected disabled>Select Municipality</option>
                                                    <option value="Padre Garcia">Padre Garcia</option>
                                                    <option value="Rosario">Rosario</option>
                                                    <option value="Taal">Taal</option>
                                                    <option value="San Jose">San Jose</option>
                                                    <option value="Lipa City">Lipa City</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-control" placeholder="Province" value="Batangas" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Postal Code</label>
                                                <input type="text" class="form-control" placeholder="Postal Code">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" rows="3" placeholder="Notes or instructions for the seller"></textarea>
                                                <small class="notes-msg" style="display:block;margin-top:5px;"></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pick Up Field (kept for existing JS) -->
                                    <div id="pickupField" style="display:none;">
                                        <input type="text" class="form-control" value="067 Bawi Padre Garcia Batangas" readonly>
                                    </div>

                                    <div id="checkoutError" class="checkout-error" style="display:none;"></div>

                                    <button type="submit" class="btn btn-secondary w-100 mt-3">Complete Order</button>
                                </form>
                            </div>

                            <!-- RIGHT: Order Summary -->
                            <div class="col-lg-4 col-md-5 d-flex">
                                <div class="order-summary w-100 h-50">
                                    <h6 class="fw-bold mb-3">ORDER SUMMARY</h6>
                                    <?php
                                    $subtotal = 0;
                                    foreach ($cart as $index => $item):
                                        $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
                                        $lineTotal = ((float)$item['price']) * $qty;
                                        $subtotal += $lineTotal;
                                        $img = $item['image'] ?? '';
                                    ?>
                                            <div class="summary-item d-flex mb-3 align-items-center">
                                                <?php if (!empty($img)): ?>
                                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars((string)$item['name']) ?>" class="summary-thumb me-3">
                                                <?php else: ?>
                                                    <div class="summary-thumb me-3" style="background:#f1f1f1;"></div>
                                                <?php endif; ?>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold"><?= $item['name'] ?></div>
                                                    <small class="text-muted">Qty: <?= $qty ?></small>
                                                </div>
                                                <div class="summary-price">₱<?= number_format($lineTotal, 2) ?></div>
                                            </div>
                                    <?php endforeach; ?>

                                    <hr>
                                    <?php
                                    $shipping = 120;
                                    $total = $subtotal + $shipping;
                                    ?>
                                    <?php if (!empty($checkoutEventDate)): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Event Date</span>
                                            <span class="fw-semibold"><?= htmlspecialchars($checkoutEventDate) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($checkoutDeliveryTime)): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Delivery Time</span>
                                            <span class="fw-semibold"><?= htmlspecialchars($checkoutDeliveryTime) ?></span>
                                        </div>
                                    <?php endif; ?>
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
                        <div class="modal-dialog modal-dialog-centered modal-sm">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title" id="cashModalLabel">Receipt</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="alert alert-success d-flex align-items-center gap-2" style="font-size:13px;">
                                        <span class="material-icons" style="font-size:18px;">check_circle</span>
                                        <div>
                                            <div class="fw-semibold">Thank you for ordering!</div>
                                            <div class="text-muted">Please view the notification for complete details.</div>
                                        </div>
                                    </div>

                                    <div class="text-center mb-3">
                                        <div class="fw-bold">YMZM Catering</div>
                                        <div class="text-muted" style="font-size:12px;">Order #<span id="cashOrderId"></span></div>
                                    </div>

                                    <div class="mb-2" style="font-size:14px;">
                                        <div><strong>Name:</strong> <span id="cashName"></span></div>
                                        <div><strong>Contact:</strong> <span id="cashContact"></span></div>
                                        <div><strong>Email:</strong> <span id="cashEmail"></span></div>
                                        <div><strong>Event Date:</strong> <span id="cashEventDate"></span></div>
                                        <div><strong>Delivery Time:</strong> <span id="cashDeliveryTime"></span></div>
                                        <div><strong>Delivery:</strong> <span id="cashDeliveryMethod"></span></div>
                                        <div id="cashAddressRow"><strong>Address:</strong> <span id="cashAddress"></span></div>
                                        <div><strong>Payment:</strong> Cash on Delivery</div>
                                    </div>

                                    <hr>

                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-2">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="text-end">Qty</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cashItems"></tbody>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-between" style="font-size:14px;">
                                        <span>Subtotal</span>
                                        <span>₱<span id="cashSubtotal"></span></span>
                                    </div>
                                    <div class="d-flex justify-content-between" style="font-size:14px;">
                                        <span>Shipping</span>
                                        <span>₱<span id="cashShipping"></span></span>
                                    </div>
                                    <div class="d-flex justify-content-between fw-bold" style="font-size:16px;">
                                        <span>Total</span>
                                        <span>₱<span id="cashTotal"></span></span>
                                    </div>

                                    <div class="alert alert-info mt-3 mb-0" style="font-size:13px;">
                                        Total Amount: ₱<span id="cashThanksTotal"></span><br>
                                        View your notifications for the complete receipt and updates.
                                    </div>
                                    <div class="alert alert-info mt-3 mb-0" style="font-size:13px;">
                                        Please prepare the exact amount for delivery.
                                    </div>
                                </div>

                                <div class="modal-footer flex-column gap-2">
                                    <button type="button" class="btn btn-primary w-100" id="cashViewNotif">View Notifications</button>
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
                                const inputGroup = input && input.closest ? input.closest('.input-group') : null;
                                if (inputGroup && inputGroup.parentNode) {
                                    inputGroup.parentNode.insertBefore(msg, inputGroup.nextSibling);
                                } else if (input && input.parentNode) {
                                    input.parentNode.appendChild(msg);
                                }
                                return msg;
                            }

                            // --- CASH MODAL REDIRECT ---
                            const cashModalCloseBtn = document.querySelector("#cashModal .btn-close");
                            if (cashModalCloseBtn) {
                                cashModalCloseBtn.addEventListener("click", () => {
                                    try { sessionStorage.setItem('openNotifications', '1'); } catch (err) {}
                                    window.location.href = "index.php?show=notifications";
                                });
                            }
                            const cashModalFooterBtn = document.querySelector("#cashModal .modal-footer button");
                            if (cashModalFooterBtn) {
                                cashModalFooterBtn.addEventListener("click", () => {
                                    try { sessionStorage.setItem('openNotifications', '1'); } catch (err) {}
                                    window.location.href = "index.php?show=notifications";
                                });
                            }

                            // --- NAME VALIDATION ---
                            const firstNameInput = document.getElementById('firstName');
                            const middleInitialInput = document.getElementById('middleInitial');
                            const lastNameInput = document.getElementById('lastName');
                            const nameMsg = createMsg(lastNameInput);

                            function validateName() {
                                const first = firstNameInput.value.trim();
                                let mid = middleInitialInput.value.trim();
                                const last = lastNameInput.value.trim();

                                if (mid.length > 1) {
                                    mid = mid.charAt(0);
                                    middleInitialInput.value = mid;
                                }

                                if (!first || !last) {
                                    nameMsg.textContent = "Enter first and last name";
                                    nameMsg.style.color = "red";
                                    lastNameInput.style.borderColor = "red";
                                    firstNameInput.style.borderColor = first ? "" : "red";
                                    return false;
                                }

                                nameMsg.textContent = "Valid name";
                                nameMsg.style.color = "green";
                                lastNameInput.style.borderColor = "green";
                                firstNameInput.style.borderColor = "green";
                                return true;
                            }

                            [firstNameInput, middleInitialInput, lastNameInput].forEach(inp => {
                                inp.addEventListener('input', validateName);
                            });

                            // --- CONTACT VALIDATION ---
                            const contactInput = document.getElementById('contactNumber');
                            contactInput.value = "09";
                            const contactMsg = createMsg(contactInput);

                            function updateContact() {
                                let val = contactInput.value.replace(/\D/g, "");
                                if (!val.startsWith("09")) {
                                    val = "09" + val.replace(/^0+/, "");
                                }
                                val = val.slice(0, 11);
                                contactInput.value = val;

                                if (val.length === 11 && val.startsWith("09")) {
                                    contactMsg.textContent = "Valid Contact";
                                    contactMsg.style.color = "green";
                                    contactInput.style.borderColor = "green";
                                    return true;
                                }

                                contactMsg.textContent = "Contact must be 11 digits starting with 09";
                                contactMsg.style.color = "red";
                                contactInput.style.borderColor = "red";
                                return false;
                            }

                            contactInput.addEventListener("input", updateContact);
                            contactInput.addEventListener("keydown", (e) => {
                                if ((e.key === "Backspace" || e.key === "Delete") && contactInput.selectionStart <= 2) e.preventDefault();
                                if (contactInput.selectionStart < 2 && e.key.length === 1) e.preventDefault();
                            });
                            contactInput.addEventListener("paste", (e) => {
                                e.preventDefault();
                                let paste = e.clipboardData.getData("text").replace(/\D/g, "");
                                if (!paste.startsWith("09")) {
                                    paste = "09" + paste.replace(/^0+/, "");
                                }
                                contactInput.value = paste.slice(0, 11);
                                updateContact();
                            });

                            // --- EMAIL VALIDATION ---
                            const emailLocalInput = document.getElementById('emailLocal');
                            const emailMsg = createMsg(emailLocalInput);


                            function normalizeEmailLocal(raw) {
                                let v = String(raw || '').trim();
                                if (v.includes('@')) v = v.split('@')[0];
                                v = v.replace(/\s+/g, '');
                                v = v.replace(/[^a-zA-Z0-9._%+\-]/g, '');
                                return v.slice(0, 64);
                            }

                            emailLocalInput.addEventListener('input', () => {
                                const normalized = normalizeEmailLocal(emailLocalInput.value);
                                if (emailLocalInput.value !== normalized) {
                                    const pos = emailLocalInput.selectionStart || normalized.length;
                                    emailLocalInput.value = normalized;
                                    try { emailLocalInput.setSelectionRange(pos, pos); } catch (e) {}
                                }

                                if (!normalized) {
                                    emailMsg.textContent = 'Email is required';
                                    emailMsg.style.color = 'red';
                                    emailLocalInput.style.borderColor = 'red';
                                    return;
                                }

                                emailMsg.textContent = 'Valid Gmail';
                                emailMsg.style.color = 'green';
                                emailLocalInput.style.borderColor = 'green';
                            });

                            // --- ADDRESS VALIDATION (Ship) ---
                            const streetInput = document.querySelector('input[placeholder="Street Number / #"]');
                            const barangayInput = document.getElementById('barangaySelect');
                            const cityInput = document.getElementById('municipalitySelect');
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

                                barangayMsg.textContent = barangayInput.value ? "Valid barangay" : "Barangay is required";
                                barangayMsg.style.color = barangayInput.value ? "green" : "red";
                                barangayInput.style.borderColor = barangayInput.value ? "green" : "red";

                                cityMsg.textContent = cityInput.value ? "Valid municipality" : "Municipality is required";
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
                                    notesMsg.textContent = text.length ? 'Valid notes' : '';
                                    notesMsg.style.color = "green";
                                    notesInput.style.borderColor = "green";
                                }
                            }

                            [streetInput, postalInput, notesInput].forEach(input => {
                                input.addEventListener("input", validateShipFields);
                            });
                            ;[barangayInput, cityInput].forEach(input => {
                                input.addEventListener('change', validateShipFields);
                            });

                            // --- MUNICIPALITY → BARANGAY OPTIONS ---
                            const barangaysByMunicipality = {
                                'Padre Garcia': [
                                    'Bawi', 'Banaba', 'Castillo', 'Cawongan', 'Manggas', 'Maugat',
                                    'Payapa', 'Quilo-quilo', 'San Felipe', 'San Miguel', 'San Vicente', 'Taug'
                                ],
                                'Rosario': [
                                    'Baybayin', 'Leviste', 'Lumbangan', 'Nasi', 'Palakpak', 'Poblacion'
                                ],
                                'Taal': [
                                    'Balisong', 'Bolbok', 'Butong', 'Caysasay', 'Ilog', 'Poblacion'
                                ],
                                'San Jose': [
                                    'Bagong Pook', 'Balagtasin', 'Bigain I', 'Bigain II', 'Calansayan', 'Poblacion'
                                ],
                                'Lipa City': [
                                    'Antipolo del Norte', 'Antipolo del Sur', 'Banaybanay', 'Bolbok', 'Bulaklakan', 'Poblacion'
                                ]
                            };

                            function renderBarangaysForMunicipality(municipality) {
                                const list = barangaysByMunicipality[municipality] || [];
                                const prev = barangayInput.value;
                                barangayInput.innerHTML = '';

                                const opt0 = document.createElement('option');
                                opt0.value = '';
                                opt0.textContent = 'Select Barangay';
                                opt0.disabled = true;
                                opt0.selected = true;
                                barangayInput.appendChild(opt0);

                                list.forEach((b) => {
                                    const opt = document.createElement('option');
                                    opt.value = b;
                                    opt.textContent = b;
                                    barangayInput.appendChild(opt);
                                });

                                if (prev && list.includes(prev)) {
                                    barangayInput.value = prev;
                                }

                                barangayInput.disabled = list.length === 0;
                            }

                            // Initialize: disable barangay until municipality selected
                            barangayInput.disabled = !cityInput.value;
                            if (cityInput.value) {
                                renderBarangaysForMunicipality(cityInput.value);
                            }
                            cityInput.addEventListener('change', () => {
                                renderBarangaysForMunicipality(cityInput.value);
                                validateShipFields();
                            });

                        });

                        // --- Complete Order ---
                        const checkoutForm = document.getElementById("checkoutForm");
                        const deliverySelectForm = document.getElementById("deliveryMethod");
                        const checkoutError = document.getElementById("checkoutError");
                        const paymentSelect = document.getElementById("paymentMethod");
                        const cashTotalSpan = document.getElementById("cashTotal");
                            const cashViewNotifBtn = document.getElementById('cashViewNotif');

                        // Receipt elements
                        const cashOrderIdEl = document.getElementById('cashOrderId');
                        const cashNameEl = document.getElementById('cashName');
                        const cashContactEl = document.getElementById('cashContact');
                        const cashEmailEl = document.getElementById('cashEmail');
                        const cashEventDateEl = document.getElementById('cashEventDate');
                        const cashDeliveryTimeEl = document.getElementById('cashDeliveryTime');
                        const cashDeliveryMethodEl = document.getElementById('cashDeliveryMethod');
                        const cashAddressRowEl = document.getElementById('cashAddressRow');
                        const cashAddressEl = document.getElementById('cashAddress');
                        const cashItemsTbody = document.getElementById('cashItems');
                        const cashSubtotalEl = document.getElementById('cashSubtotal');
                        const cashShippingEl = document.getElementById('cashShipping');
                        const cashThanksTotalEl = document.getElementById('cashThanksTotal');

                        const receiptItems = <?= json_encode(array_map(function ($it) {
                            $name = (string)($it['name'] ?? ($it['product_name'] ?? ''));
                            $qty = isset($it['qty']) ? (int)$it['qty'] : 1;
                            $price = isset($it['price']) ? (float)$it['price'] : 0.0;
                            return [
                                'name' => $name,
                                'qty' => $qty,
                                'line_total' => $price * $qty,
                            ];
                        }, $cart), JSON_UNESCAPED_UNICODE) ?>;

                        function formatMoney(n) {
                            const num = Number(n || 0);
                            return num.toFixed(2);
                        }

                        function renderReceiptItems() {
                            if (!cashItemsTbody) return;
                            cashItemsTbody.innerHTML = '';
                            (receiptItems || []).forEach((it) => {
                                const tr = document.createElement('tr');
                                const tdName = document.createElement('td');
                                tdName.textContent = String(it.name || '');
                                const tdQty = document.createElement('td');
                                tdQty.className = 'text-end';
                                tdQty.textContent = String(it.qty || 0);
                                const tdTotal = document.createElement('td');
                                tdTotal.className = 'text-end';
                                tdTotal.textContent = '₱' + formatMoney(it.line_total);
                                tr.appendChild(tdName);
                                tr.appendChild(tdQty);
                                tr.appendChild(tdTotal);
                                cashItemsTbody.appendChild(tr);
                            });
                        }

                        checkoutForm.addEventListener("submit", function(e) {
                            e.preventDefault();

                            checkoutError.style.display = "none";
                            checkoutError.textContent = "";

                            const first = (document.getElementById('firstName')?.value || '').trim();
                            const midRaw = (document.getElementById('middleInitial')?.value || '').trim();
                            const mid = midRaw ? midRaw.charAt(0) : '';
                            const last = (document.getElementById('lastName')?.value || '').trim();
                            const name = `${first} ${mid ? mid + ' ' : ''}${last}`.trim();

                            const contactInputEl = document.getElementById('contactNumber');
                            const contact = (contactInputEl?.value || '').trim();
                            const contactDigits = contact.replace(/\D/g, '');

                            const emailLocal = (document.getElementById('emailLocal')?.value || '').trim();
                            const email = (emailLocal.includes('@') ? emailLocal.split('@')[0] : emailLocal).replace(/\s+/g, '') + '@gmail.com';
                            const delivery = deliverySelectForm.value;
                            const payment = paymentSelect.value;
                            const deliveryTime = <?= json_encode((string)$checkoutDeliveryTime) ?>;

                            const showError = (msg) => {
                                checkoutError.textContent = msg;
                                checkoutError.style.display = "block";
                            };

                            if (!first || !last) {
                                showError("First and last name are required");
                                return;
                            }

                            if (!contactDigits.startsWith('09') || contactDigits.length !== 11) {
                                showError("Valid contact number is required (11 digits starting with 09)");
                                return;
                            }

                            if (!emailLocal) {
                                showError("Email is required");
                                return;
                            }

                            if (!delivery || !payment) {
                                showError("Please select delivery and payment method");
                                return;
                            }

                            if (!deliveryTime) {
                                showError("Delivery time is required. Please set it in cart before checkout.");
                                return;
                            }

                            if (delivery === "ship") {
                                const shipFields = [
                                    'input[placeholder="Street Number / #"]',
                                    '#barangaySelect',
                                    '#municipalitySelect',
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
                            const eventDate = <?= json_encode((string)$checkoutEventDate) ?>;

                            // Remember email for notifications widget
                            try {
                                localStorage.setItem('notifEmail', email);
                            } catch (err) {}

                            // Persist order to backend
                            fetch("admin/processcheckout.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: new URLSearchParams({
                                    name,
                                    contact,
                                    email,
                                    payment,
                                    delivery,
                                    event_date: eventDate,
                                    delivery_time: deliveryTime,
                                    street: document.querySelector('input[placeholder="Street Number / #"]').value,
                                    barangay: document.getElementById('barangaySelect').value,
                                    city: document.getElementById('municipalitySelect').value,
                                    province: "Batangas",
                                    postal: document.querySelector('input[placeholder="Postal Code"]').value,
                                    notes: document.querySelector('textarea').value,
                                    shipping: shipping,
                                    total: totalAmount
                                })
                            })
                            .then(async (res) => {
                                const text = await res.text();
                                let data;
                                try {
                                    data = JSON.parse(text);
                                } catch (err) {
                                    // API sometimes returns plain text like "error:..."; surface it to the user.
                                    throw new Error(text || 'Unexpected response');
                                }
                                return data;
                            })
                            .then(data => {

                                if (data.status === "success") {

                                    const orderId = data.order_id;
                                    const totalAmount = data.total;

                                    if (payment === "cash") {
                                        if (cashOrderIdEl) cashOrderIdEl.textContent = String(orderId);
                                        if (cashNameEl) cashNameEl.textContent = name;
                                        if (cashContactEl) cashContactEl.textContent = contact;
                                        if (cashEmailEl) cashEmailEl.textContent = email;
                                        if (cashEventDateEl) cashEventDateEl.textContent = eventDate || '-';
                                        if (cashDeliveryTimeEl) cashDeliveryTimeEl.textContent = deliveryTime || '-';
                                        if (cashDeliveryMethodEl) cashDeliveryMethodEl.textContent = delivery;

                                        if (cashAddressRowEl && cashAddressEl) {
                                            if (delivery === 'ship') {
                                                const streetVal = document.querySelector('input[placeholder="Street Number / #"]').value.trim();
                                                const brgyVal = document.getElementById('barangaySelect').value;
                                                const cityVal = document.getElementById('municipalitySelect').value;
                                                const postalVal = document.querySelector('input[placeholder="Postal Code"]').value.trim();
                                                cashAddressEl.textContent = `${streetVal}, ${brgyVal}, ${cityVal}, Batangas ${postalVal}`;
                                                cashAddressRowEl.style.display = '';
                                            } else {
                                                cashAddressEl.textContent = '';
                                                cashAddressRowEl.style.display = 'none';
                                            }
                                        }

                                        renderReceiptItems();

                                        if (cashSubtotalEl) cashSubtotalEl.textContent = formatMoney(<?= (float)$subtotal ?>);
                                        if (cashShippingEl) cashShippingEl.textContent = formatMoney(shipping);
                                        if (cashTotalSpan) cashTotalSpan.textContent = formatMoney(totalAmount);
                                        if (cashThanksTotalEl) cashThanksTotalEl.textContent = formatMoney(totalAmount);
                                        try { sessionStorage.setItem('openNotifications', '1'); } catch (err) {}
                                        const cashModal = new bootstrap.Modal(document.getElementById('cashModal'));
                                        cashModal.show();
                                            if (cashViewNotifBtn) {
                                                cashViewNotifBtn.onclick = () => {
                                                    try { sessionStorage.setItem('openNotifications', '1'); } catch (err) {}
                                                    window.location.href = 'index.php?show=notifications';
                                                };
                                            }
                                    } else if (payment === "paypal") {
                                        sessionStorage.setItem("order_id", orderId);
                                        window.location.href = `payment.php?order_id=${encodeURIComponent(orderId)}`;
                                    }

                                } else {
                                    checkoutError.textContent = data.message || "Checkout failed. Please try again.";
                                    checkoutError.style.display = "block";
                                }
                            })
                            .catch((err) => {
                                const msg = (err && err.message) ? err.message : "Network error. Please try again.";
                                checkoutError.textContent = msg;
                                checkoutError.style.display = "block";
                            });

                        });
                    </script>

                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
                </body>

                </html>