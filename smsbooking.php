<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

// Guests cannot proceed
$isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
if (!$isLoggedIn) {
    $currentUri = $_SERVER['REQUEST_URI'] ?? 'smsbooking.php';
    header('Location: auth.php?next=' . urlencode($currentUri));
    exit;
}

// If coming from cart.php, persist selected items + updated quantities for this checkout.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartAll = $_SESSION['cart'] ?? [];
    $qtyMap = $_POST['qty'] ?? [];

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

    // Persist selected event date (date-only)
    $eventDate = trim((string)($_POST['event_date'] ?? ''));
    if ($eventDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
        $minDate = date('Y-m-d', strtotime('+3 days'));
        if ($eventDate < $minDate) {
            unset($_SESSION['checkout_event_date']);
            $_SESSION['cart'] = $cartAll;
            header('Location: cart.php?error=date');
            exit;
        }
        $_SESSION['checkout_event_date'] = $eventDate;
    } else {
        unset($_SESSION['checkout_event_date']);
        $_SESSION['cart'] = $cartAll;
        header('Location: cart.php?error=date');
        exit;
    }

    // Persist delivery time (time-only)
    $deliveryTime = trim((string)($_POST['delivery_time'] ?? ''));
    if ($deliveryTime !== '' && preg_match('/^\d{2}:\d{2}$/', $deliveryTime)) {
        // Basic availability window (08:00–18:00)
        if ($deliveryTime < '08:00' || $deliveryTime > '18:00') {
            unset($_SESSION['checkout_delivery_time']);
            $_SESSION['cart'] = $cartAll;
            header('Location: cart.php?error=time');
            exit;
        }
        $_SESSION['checkout_delivery_time'] = $deliveryTime;
    } else {
        unset($_SESSION['checkout_delivery_time']);
        $_SESSION['cart'] = $cartAll;
        header('Location: cart.php?error=time');
        exit;
    }

    // Clear old SMS booking session to ensure each checkout gets a fresh booking reference
    unset($_SESSION['sms_booking_ref'], $_SESSION['sms_phone'], $_SESSION['sms_confirmed'], $_SESSION['sms_confirmed_at']);
}

// Each checkout attempt should get its own booking ref (don't reuse from previous checkout)
$existingBookingRef = '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS Booking Confirmation</title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .sms-box {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 5vh auto;
            text-align: center;
        }

        .sms-title {
            font-size: 2rem;
            line-height: 1.1;
            margin-bottom: 0.75rem;
        }

        .sms-help {
            color: #555;
            margin-bottom: 1rem;
        }

        .sms-box input {
            width: 100%;
            padding: 10px;
            margin: 0.5rem 0 1rem 0;
            font-size: 1rem;
            border-radius: 0.3rem;
            border: 1px solid #ced4da;
        }

        .sms-box button {
            width: 100%;
        }

        #status {
            margin-top: 1rem;
            font-weight: bold;
        }

        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1080;
        }

        .back-action {
            cursor: pointer;
            user-select: none;
            width: fit-content;
            text-decoration: none;
            color: #1f1f1f;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 999px;
            padding: 6px 10px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .back-action:hover {
            background: #fff;
            border-color: rgba(0, 0, 0, 0.14);
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="my-5 mx-4">
            <a class="d-inline-flex align-items-center border bg-light rounded-5 px-2 py-1 back-action gap-2 text-decoration-none text-dark" href="cart.php">
                <i class="material-icons">&#xe5c4;</i>
                <span>back</span>
            </a>
        </div>
        <div class="sms-box card-admin">
            <div class="sms-title">SMS Booking<br>Confirmation</div>
            <div class="sms-help">Please enter your phone number to send an OTP via SMS to confirm your order.</div>

            <input type="text" id="phone" class="form-control" placeholder="09XXXXXXXXX" required>

            <button id="sendSmsBtn" class="btn btn-success mt-2">Click to Send OTP</button>

            <p id="status"></p>
        </div>
    </div>

    <div class="toast-container">
        <div id="smsToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="smsToastBody">We have received your confirmation.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (() => {
            const existingBookingRef = <?= json_encode((string)$existingBookingRef) ?>;
            const phoneInput = document.getElementById("phone");
            const sendBtn = document.getElementById("sendSmsBtn");
            const statusEl = document.getElementById("status");
            const toastEl = document.getElementById('smsToast');
            const toastBody = document.getElementById('smsToastBody');

            let pollTimer = null;
            let currentBookingRef = existingBookingRef || '';
            let currentPhone = '';
            let cooldownTimer = null;
            let cooldownRemaining = 0;

            function setButtonCooldown(seconds) {
                if (cooldownTimer) {
                    clearInterval(cooldownTimer);
                    cooldownTimer = null;
                }
                cooldownRemaining = Math.max(0, seconds | 0);
                sendBtn.disabled = true;
                const baseLabel = currentBookingRef ? 'Resend OTP' : 'Click to Send OTP';
                sendBtn.textContent = `${baseLabel} (${cooldownRemaining}s)`;
                cooldownTimer = setInterval(() => {
                    cooldownRemaining -= 1;
                    if (cooldownRemaining <= 0) {
                        clearInterval(cooldownTimer);
                        cooldownTimer = null;
                        sendBtn.disabled = false;
                        sendBtn.textContent = currentBookingRef ? 'Resend OTP' : 'Click to Send OTP';
                        return;
                    }
                    const label = currentBookingRef ? 'Resend OTP' : 'Click to Send OTP';
                    sendBtn.textContent = `${label} (${cooldownRemaining}s)`;
                }, 1000);
            }

            function showToast(message, ok = true) {
                toastEl.classList.remove('text-bg-success', 'text-bg-danger');
                toastEl.classList.add(ok ? 'text-bg-success' : 'text-bg-danger');
                toastBody.textContent = message;
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 2500
                });
                toast.show();
            }

            function stopPolling() {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            async function pollStatus() {
                if (!currentBookingRef) return;
                try {
                    const res = await fetch('check_booking_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            booking_ref: currentBookingRef,
                            phone: currentPhone
                        })
                    });
                    const data = await res.json();
                    if (!data || !data.success) return;

                    const status = String(data.status || '').toUpperCase();
                    if (status === 'CONFIRMED') {
                        stopPolling();
                        statusEl.innerText = "✅ Confirmation received. Redirecting you back to checkout...";

                        // Persist SMS-confirmed flag in the client session
                        const confirmRes = await fetch('sms_confirm_session.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                booking_ref: currentBookingRef,
                                phone: currentPhone
                            })
                        });
                        const confirmData = await confirmRes.json();
                        if (!confirmData || !confirmData.success) {
                            showToast('Confirmed by SMS, but session sync failed. Please refresh.', false);
                            sendBtn.disabled = false;
                            return;
                        }

                        showToast('We have received your confirmation.', true);
                        setTimeout(() => {
                            window.location.href = 'checkout.php?sms=confirmed';
                        }, 2500);
                    } else if (status === 'CANCELLED') {
                        stopPolling();
                        statusEl.innerText = "❌ Booking cancelled via SMS. You can resend an OTP.";
                        showToast('Booking cancelled. You can resend OTP.', false);
                        sendBtn.disabled = false;
                    } else {
                        statusEl.innerText = "✅ SMS sent. Reply YES <OTP> to confirm your order.";
                    }
                } catch (e) {
                    // ignore transient errors
                }
            }

            // If we already have a booking ref, treat next send as a resend
            if (currentBookingRef) {
                sendBtn.textContent = 'Resend OTP';
            }

            sendBtn.addEventListener("click", async function() {
                const phone = phoneInput.value.trim();
                const btn = this;

                if (phone === "") {
                    statusEl.innerText = "❌ Please enter your phone number to send an OTP code via SMS.";
                    return;
                }

                btn.disabled = true;
                statusEl.innerText = "📩 Sending SMS...";

                currentPhone = phone;

                try {
                    // Each (re)send should generate a new booking reference
                    currentBookingRef = '';

                    const res = await fetch("send_sms.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            phone: phone,
                            force_new_booking: true
                        })
                    });

                    const data = await res.json();
                    if (data && data.success) {
                        if (data.booking_ref) {
                            currentBookingRef = data.booking_ref;
                        }
                        statusEl.innerText = "✅ SMS sent. Reply YES <OTP> to confirm your order.";

                        // UI: allow resend after a short cooldown
                        btn.textContent = 'Resend OTP';
                        setButtonCooldown(12);

                        stopPolling();
                        pollTimer = setInterval(pollStatus, 2500);
                        // Immediate first check
                        pollStatus();
                    } else {
                        statusEl.innerText = "❌ " + (data && data.message ? data.message : 'Failed to send SMS');
                        btn.disabled = false;
                    }
                } catch (e) {
                    statusEl.innerText = "⚠️ Server error.";
                    btn.disabled = false;
                }
            });
        })();
    </script>

</body>

</html>