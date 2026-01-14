<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS Booking Confirmation</title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

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
    </style>
</head>

<body>

<div class="container">
    <div class="sms-box card-admin">
        <h2 class="mb-3">SMS Booking Confirmation</h2>
        <p>Please enter your phone number to send an OTP via SMS to confirm your order.</p>

        <input type="text" id="phone" class="form-control" placeholder="+639XXXXXXXXX" required>

        <button id="sendSmsBtn" class="btn btn-success mt-2">Click to Send OTP</button>

        <p id="status"></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById("sendSmsBtn").addEventListener("click", function () {
    const phone = document.getElementById("phone").value.trim();
    const status = document.getElementById("status");
    const btn = this;

    if (phone === "") {
        status.innerText = "❌ Please enter your phone number to send an OTP code via SMS.";
        return;
    }

    btn.disabled = true;
    status.innerText = "📩 Sending SMS...";

    fetch("send_sms.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            phone: phone,
            booking_id: "TEST-BOOKING-001"
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            status.innerText = "✅ SMS sent. Reply YES <OTP> to confirm your order.";
        } else {
            status.innerText = "❌ " + data.message;
            btn.disabled = false;
        }
    })
    .catch(() => {
        status.innerText = "⚠️ Server error.";
        btn.disabled = false;
    });
});
</script>

</body>
</html>
