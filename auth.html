<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</head>
<style>
    body {
        background: linear-gradient(to bottom, #814142, #3c2a27);
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .auth-toggle {
        position: relative;
        width: 300px;
        height: 50px;
        background: #f1f1f1;
        border-radius: 50px;
        display: flex;
        overflow: hidden;
        margin: auto 0 2rem 0;
    }

    .toggle-bg {
        position: absolute;
        width: 50%;
        height: 100%;
        background: #212529;
        border-radius: 50px;
        transition: transform 0.4s ease;
    }

    .toggle-btn {
        width: 50%;
        border: none;
        background: transparent;
        z-index: 1;
        font-weight: 600;
        font-size: 15px;
        transition: color 0.3s ease;
    }

    .toggle-btn.active {
        color: #fff;
    }

    .form-check-label {
        user-select: none;
    }

    .auth-form {
        opacity: 1;
        transition: opacity 0.3s ease;
    }

    .auth-form.fade-out {
        opacity: 0;
    }
</style>
</head>

<body>
    <div class="auth-wrapper">
        <div class="card auth-card shadow rounded-4 p-5" style="width: 450px; background: #ffffff;">
            <div class="text-center mb-3">
                <img src="images/YMZM-logo.png" alt="Logo" class="rounded-circle" style="width:90px; height:90px;">
            </div>

            <!-- toggle -->
            <div class="d-flex justify-content-center my-2">
                <div class="auth-toggle mb-4">
                    <div class="toggle-bg"></div>
                    <button id="signInBtn" class="toggle-btn active">Sign In</button>
                    <button id="signUpBtn" class="toggle-btn">Sign Up</button>
                </div>
            </div>
            <!-- alert -->
            <div id="authAlert" class="alert alert-danger d-none text-dark" role="alert">
                Please fill in all required fields correctly.
            </div>
            <!-- sign in -->
            <form id="signInForm" class="auth-form">
                <div class="mb-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control border border-dark rounded-3" placeholder="Enter email">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control border border-dark rounded-3"
                        placeholder="Enter password">
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input border border-dark" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="small">Forgot Password?</a>
                </div>
                <button class="btn btn-dark w-100 py-2 mb-3">Sign In</button>
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1">
                    <span class="mx-2">Or</span>
                    <hr class="flex-grow-1">
                </div>
                <div class="text-center">
                    <i class="bi bi-google fs-4 mx-3"></i>
                    <i class="bi bi-facebook fs-4 mx-3"></i>
                    <i class="bi bi-apple fs-4 mx-3"></i>
                </div>
            </form>
            <!-- sign up -->
            <form id="signUpForm" class="auth-form d-none">
                <div class="mb-4">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control border border-dark rounded-3" placeholder="Enter name">
                </div>
                <div class="mb-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control border border-dark rounded-3" placeholder="Enter email">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control border border-dark rounded-3"
                        placeholder="Enter password">
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input border border-dark" type="checkbox" id="termsCheck">
                    <label class="form-check-label" for="termsCheck">
                        I accept the Terms & Conditions
                    </label>
                </div>
                <button class="btn btn-dark w-100 py-2">Sign Up</button>
            </form>

        </div>
    </div>

    <!--js -->
    <script>
        const signInBtn = document.getElementById("signInBtn");
        const signUpBtn = document.getElementById("signUpBtn");
        const toggleBg = document.querySelector(".toggle-bg");
        const signInForm = document.getElementById("signInForm");
        const signUpForm = document.getElementById("signUpForm");
        const authAlert = document.getElementById("authAlert");

        // alert function
        function showError(message) {
            authAlert.textContent = message;
            authAlert.classList.remove("d-none");

            setTimeout(() => {
                authAlert.classList.add("d-none");
            }, 3000);
        }

        // toggle forms
        signInBtn.onclick = () => {
            toggleBg.style.transform = "translateX(0)";
            signInBtn.classList.add("active");
            signUpBtn.classList.remove("active");
            signUpForm.classList.add("fade-out");
            setTimeout(() => {
                signUpForm.classList.add("d-none");
                signUpForm.classList.remove("fade-out");
                signInForm.classList.remove("d-none");
            }, 300);
        };

        signUpBtn.onclick = () => {
            toggleBg.style.transform = "translateX(100%)";
            signUpBtn.classList.add("active");
            signInBtn.classList.remove("active");
            signInForm.classList.add("fade-out");
            setTimeout(() => {
                signInForm.classList.add("d-none");
                signInForm.classList.remove("fade-out");

                signUpForm.classList.remove("d-none");
            }, 300);
        };

        // sign in validation
        signInForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const email = signInForm.querySelector('input[type="email"]').value.trim();
            const password = signInForm.querySelector('input[type="password"]').value.trim();
            if (!email || !password) {
                showError("Email and password are required.");
                return;
            }
            alert("Sign In successful!");
        });

        // sign up validation
        signUpForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const name = signUpForm.querySelector('input[type="text"]').value.trim();
            const email = signUpForm.querySelector('input[type="email"]').value.trim();
            const password = signUpForm.querySelector('input[type="password"]').value.trim();
            const terms = document.getElementById("termsCheck").checked;
            if (!name || !email || !password) {
                showError("All fields are required.");
                return;
            }
            if (!terms) {
                showError("You must accept the Terms & Conditions.");
                return;
            }
            alert("Sign Up successful!");
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>