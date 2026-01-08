<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(180deg, #f7f4f3 0%, #865d68 65%, #5b2333 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            position: relative;
            /* add this */
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            width: 400px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        input {
            width: 80%;
            padding: 10px;
            margin: 15px 0;
            font-size: 16px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            background: #a33252;
            color: #fff;
            cursor: pointer;
        }

        button:hover {
            background: #601f36;
        }

        .message-box {
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #f0f0f0;
            position: relative;
        }

        #exit-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #a33252;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        #exit-btn:hover {
            background: #601f36;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Forgot Password</h2>

        <!-- Step 1: Wait message in textbox (directly shown) -->
        <div id="step-wait" class="message-box">
            Please wait, we will send the code...
        </div>


        <!-- Step 2: Code input -->
        <div id="step-code" class="hidden">
            <input type="text" id="code" placeholder="Enter code" />
            <br>
            <button onclick="verifyCode()">Verify Code</button>
            <div class="message-box">
                Didn't receive code? <span style="color:blue;cursor:pointer;" onclick="resendCode()">Resend</span>
            </div>
            <div style="margin-top:10px;">
                <span style="color:red;cursor:pointer;" onclick="goHome()">Go back to homepage</span>
            </div>
        </div>

        <!-- Step 3: Reset password -->
        <div id="step-reset" class="hidden">
            <input type="password" id="newPassword" placeholder="New password" />
            <br>
            <button onclick="resetPassword()">Reset Password</button>
        </div>

        <div id="final-message" class="message-box hidden"></div>
    </div>

    <script>
        let verificationCode = "";
        const urlParams = new URLSearchParams(window.location.search);
        let emailGlobal = urlParams.get('email'); // use whatever the user typed

        if (!emailGlobal) {
            alert("No email provided. Please go back and enter your email.");
            // Optionally redirect back to auth page
            window.location.href = "auth.php";
        }

        // Automatically send code on page load
        window.onload = function () {
            sendCode();
        }

        function sendCode() {
            document.getElementById('step-code').classList.add('hidden');
            document.getElementById('step-reset').classList.add('hidden');
            document.getElementById('step-wait').classList.remove('hidden');

            // Call backend to send code via admin Gmail
            fetch('send_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: emailGlobal }) // the user's email
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        console.log('Code sent to email');
                        setTimeout(showCodeInput, 3000); // show code input after 3 seconds
                    } else {
                        alert('Error sending code: ' + data.message);
                    }
                })
                .catch(err => alert('Error sending code'));
        }


        function showCodeInput() {
            document.getElementById('step-wait').classList.add('hidden');
            document.getElementById('step-code').classList.remove('hidden');
        }

        function verifyCode() {
            const code = document.getElementById('code').value;

            fetch('verify_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Code correct, show reset password form
                        document.getElementById('step-code').classList.add('hidden');
                        document.getElementById('step-reset').classList.remove('hidden');
                    } else {
                        alert('Incorrect code! Please try again.');
                    }
                })
                .catch(err => alert('Error verifying code'));
        }

        function resendCode() {
            sendCode();
        }

        function goHome() {
            window.location.href = "index.html"; // Change to your homepage
        }

        function resetPassword() {
            const newPassword = document.getElementById('newPassword').value;
            if (!newPassword) {
                alert("Enter a new password!");
                return;
            }

            // Simulate backend password reset
            fetch('reset_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: emailGlobal, password: newPassword })
            })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('step-reset').classList.add('hidden');
                    document.getElementById('final-message').textContent = data.message || "Password reset successful!";
                    document.getElementById('final-message').classList.remove('hidden');
                })
                .catch(err => alert("Error resetting password."));
        }
    </script>
</body>

</html>