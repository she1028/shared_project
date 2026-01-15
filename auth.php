<?php
include("connect.php"); // $conn and executeQuery()
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

// Helper: sanitize and produce safe "next" redirect (internal only)
function getSafeNext() {
    $next = null;
    if (!empty($_GET['next'])) $next = $_GET['next'];
    elseif (!empty($_POST['next'])) $next = $_POST['next'];

     if ($next) {
        // Reject absolute URLs (prevent open redirect)
        if (strpos($next, 'http://') === false && strpos($next, 'https://') === false) {
            $candidate = str_replace("\0", '', $next);
            if (strpos($candidate, '..') === false) {
                // allow relative path starting with / or a filename
                return $candidate;
            }
        }
    }
    return null;
}

// ==================== SIGN UP ====================
if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
    if (!isset($_POST['terms'])) {
        $signup_error = "You must accept the Terms & Conditions to sign up!";
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $rawPassword = $_POST['password'];
        $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);
        $role = 'user'; // force normal user

        // Check if email exists
        $check = executeQuery("SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $signup_error = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $passwordHash, $role);
            if ($stmt->execute()) {
                // Auto-login the newly created user
                $newUserId = $stmt->insert_id ?: $conn->insert_id;
                // CONSISTENT SESSION KEYS (set several common variants)
                $_SESSION['userID']  = $newUserId;
                $_SESSION['userId']  = $newUserId;
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['name']    = $name;
                $_SESSION['role']    = $role;
                $_SESSION['email']   = $email;

                // Redirect to safe next or index
                $safeNext = getSafeNext();
                // Prevent normal users from being routed to /admin
                if ($safeNext) {
                    $normalizedNext = ltrim($safeNext, '/');
                    if (stripos($normalizedNext, 'admin/') === 0) {
                        $safeNext = null;
                    }
                }

                if ($safeNext) {
                    header("Location: {$safeNext}");
                    exit();
                } else {
                    header("Location: index.php");
                    exit();
                }

            } else {
                 $signup_error = "Sign Up failed. Please try again.";
            }
            $stmt->close();
        }
    }
}

// ==================== SIGN IN ====================
if (isset($_POST['email'], $_POST['password']) && !isset($_POST['name'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $res = executeQuery("SELECT * FROM users WHERE email='$email'");
    if ($res && mysqli_num_rows($res) == 1) {
        $user = mysqli_fetch_assoc($res);
        if (password_verify($password, $user['password'])) {
            // Normalize DB id key: accept userId or userID
            $dbUserId = null;
            if (isset($user['userId'])) $dbUserId = $user['userId'];
            elseif (isset($user['userID'])) $dbUserId = $user['userID'];
            else {
                // fallback to any id-like field
                foreach ($user as $k => $v) {
                    if (stripos($k, 'id') !== false) { $dbUserId = $v; break; }
                }
            }

            // Admin accounts must not sign in via client auth (use generic message)
            if (($user['role'] ?? '') === 'admin') {
                $signin_error = 'Invalid email';
            } else {
                // CONSISTENT SESSION KEYS
                $_SESSION['userID']  = $dbUserId;
                $_SESSION['userId']  = $dbUserId;
                $_SESSION['user_id'] = $dbUserId;
                $_SESSION['name']    = $user['name'] ?? '';
                $_SESSION['role']    = $user['role'] ?? 'user';
                $_SESSION['email']   = $user['email'] ?? '';

            // Determine safe next redirect
            $safeNext = getSafeNext();

            // Prevent normal users from being routed to /admin
            if ($safeNext) {
                $normalizedNext = ltrim($safeNext, '/');
                if (stripos($normalizedNext, 'admin/') === 0) {
                    $safeNext = null;
                }
            }

                // Redirect (client only)
                if ($safeNext) {
                    header("Location: {$safeNext}");
                    exit();
                } else {
                    header("Location: index.php");
                    exit();
                }
            }
        } else {
            $signin_error = "Incorrect password!";
        }
    } else {
        $signin_error = "Email not registered!";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="auth.css">

</head>

<body>
    <div class="bg-blur"></div>
    <div class="container-fluid auth-wrapper d-flex align-items-center justify-content-center">
        <div class="card auth-card shadow rounded-4 p-4 p-md-5">

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
            <?php if (isset($signup_error)) : ?>
                <div class="alert alert-danger text-dark"><?php echo $signup_error; ?></div>
            <?php endif; ?>
            <?php if (isset($signup_success)) : ?>
                <div class="alert alert-success text-dark"><?php echo $signup_success; ?></div>
            <?php endif; ?>
            <?php if (isset($signin_error)) : ?>
                <div class="alert alert-danger text-dark"><?php echo $signin_error; ?></div>
            <?php endif; ?>

            <!-- sign in -->
            <form id="signInForm" class="auth-form" method="POST" action="">
                <div class="mb-4"> <label class="form-label">Email</label> <input type="email" id="signInEmail" name="email" class="form-control border-dark rounded-3" placeholder="Enter email" required> </div>
                <div class="mb-3">
                    <div class="position-relative"> <input type="password" id="signInPassword" class="form-control" placeholder="Enter password" name="password" required> <i class="bi bi-eye-slash password-toggle" data-target="signInPassword"></i> </div>
                </div>
                <div class="d-flex justify-content-between mb-4">
                     <a id="forgotPasswordLink" href="forgot_password.php" class="small">Forgot Password?</a>
                </div> <button type="submit" class="btn btn-dark w-100 py-2 mb-3">Sign In</button>
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1"> <span class="mx-2">or</span>
                    <hr class="flex-grow-1">
                </div>
                
                <!-- Bake & Take Login Button -->
                <button type="button" id="bakeAndTakeLoginBtn" class="btn btn-outline-light w-100 py-2 mb-3 d-flex align-items-center justify-content-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                    </svg>
                    Login with Bake & Take
                </button>
                
                <div id="bakeAndTakeError" class="alert alert-danger text-dark d-none"></div>
                <div id="bakeAndTakeLoading" class="text-center d-none">
                    <div class="spinner-border spinner-border-sm text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Authenticating with Bake & Take...</span>
                </div>
                
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1"> <span class="mx-2">Visit us on</span>
                    <hr class="flex-grow-1">
                </div>
                <div class="text-center"> <i class="bi bi-google fs-4 mx-3"></i> <i class="bi bi-facebook fs-4 mx-3"></i> <i class="bi bi-apple fs-4 mx-3"></i> </div>
            </form>

            <!-- sign up -->
            <form id="signUpForm" class="auth-form d-none" method="POST" action="">
                <div class="mb-4"> <label class="form-label">Name</label> <input type="text" name="name" class="form-control border-dark rounded-3" placeholder="Enter name" required> </div>
                <div class="mb-4"> <label class="form-label">Email</label> <input type="email" name="email" class="form-control border-dark rounded-3" placeholder="Enter email" required> </div>
                <div class="mb-3"> <label class="form-label">Password</label>
                    <div class="position-relative"> <input type="password" id="signUpPassword" class="form-control" placeholder="Enter password" name="password" required> <i class="bi bi-eye-slash password-toggle" data-target="signUpPassword"></i> </div>
                    <div id="passwordNotice" class="form-text text-warning mt-1"> Password must be at least 8 characters, include uppercase, lowercase, and a special character. </div>
                </div>
                <div class="form-check mb-4"> <input class="form-check-input border-dark" type="checkbox" id="termsCheck" name="terms"> <label class="form-check-label" for="termsCheck"> I accept the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-underline text-light">Terms & Conditions</a> </label> </div>
                <div id="termsNotice" class="form-text text-warning d-none mb-3"> You must accept the Terms & Conditions to sign up! </div> <button type="submit" class="btn btn-outline-light w-100 py-2">Sign Up</button>
            </form>
        </div>
    </div>

    <!-- Terms & Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content custom-modal"> <!-- Add custom-modal here -->
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms & Conditions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>By signing up, you agree to the following:</p>

                    <h5>Eligibility</h5>
                    <ul>
                        <li>You must be at least 18 years old to create an account.</li>
                        <li>You must provide accurate, current, and complete information during registration.</li>
                    </ul>

                    <h5>Account Responsibility</h5>
                    <ul>
                        <li>You are responsible for maintaining the confidentiality of your account and password.</li>
                        <li>Any activity under your account is your responsibility. Notify us immediately if you suspect unauthorized use.</li>
                    </ul>

                    <h5>Service Use</h5>
                    <ul>
                        <li>The account is solely for personal or business use related to ordering catering services.</li>
                        <li>You may not use the platform for illegal or unauthorized purposes.</li>
                    </ul>

                    <h5>Orders and Payments</h5>
                    <ul>
                        <li>All orders placed through the account are binding and must be paid according to the payment terms.</li>
                        <li>Prices, availability, and menu items are subject to change without prior notice.</li>
                    </ul>

                    <h5>Cancellations and Refunds</h5>
                    <ul>
                        <li>Cancellations must be made according to the policy posted on our website.</li>
                        <li>Refunds, if applicable, will follow our standard refund process.</li>
                    </ul>

                    <h5>Communication</h5>
                    <ul>
                        <li>By creating an account, you agree to receive emails, notifications, and updates regarding your orders and promotions.</li>
                        <li>You can opt-out of promotional emails at any time.</li>
                    </ul>

                    <h5>Content and Conduct</h5>
                    <ul>
                        <li>You agree not to post or submit content that is offensive, illegal, or infringes on others’ rights.</li>
                        <li>We reserve the right to remove inappropriate content or suspend accounts violating these rules.</li>
                    </ul>

                    <h5>Limitation of Liability</h5>
                    <ul>
                        <li>We are not responsible for any losses, damages, or injuries caused by using the platform or receiving the services.</li>
                        <li>Our responsibility is limited to the extent permitted by law.</li>
                    </ul>

                    <h5>Termination</h5>
                    <ul>
                        <li>We may suspend or terminate your account for violation of these Terms & Conditions or fraudulent activity.</li>
                    </ul>

                    <h5>Changes to Terms</h5>
                    <ul>
                        <li>We may update these Terms & Conditions at any time. Users will be notified of significant changes, and continued use of the account constitutes acceptance of the updated terms.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
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
        const passwordInput = document.getElementById("signUpPassword");
        const passwordNotice = document.getElementById("passwordNotice");
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

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


        // Password strength validation
        passwordInput.addEventListener("input", () => {
            const isValid = passwordRegex.test(passwordInput.value);
            passwordNotice.textContent = isValid ? "Password is strong!" : "Password must be at least 8 characters, include uppercase, lowercase, and a special character.";
            passwordNotice.classList.toggle("text-success", isValid);
            passwordNotice.classList.toggle("text-warning", !isValid);
        });

        // Persist the email typed on Sign In for the reset flow
        const signInEmailInput = document.getElementById('signInEmail');
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');

        function setLastAuthEmailCookie(value) {
            if (!value) return;
            const maxAge = 60 * 60; // 1 hour
            document.cookie = 'last_auth_email=' + encodeURIComponent(value) + '; path=/; max-age=' + maxAge + '; samesite=lax';
        }

        if (signInEmailInput) {
            // Handle browser autofill (may not fire input event)
            if (signInEmailInput.value && signInEmailInput.value.trim()) {
                setLastAuthEmailCookie(signInEmailInput.value.trim());
            } else {
                let tries = 0;
                const t = setInterval(function () {
                    tries++;
                    if (signInEmailInput.value && signInEmailInput.value.trim()) {
                        setLastAuthEmailCookie(signInEmailInput.value.trim());
                        clearInterval(t);
                    }
                    if (tries >= 20) clearInterval(t);
                }, 100);
            }

            signInEmailInput.addEventListener('input', function () {
                setLastAuthEmailCookie(signInEmailInput.value.trim());
            });
            signInEmailInput.addEventListener('change', function () {
                setLastAuthEmailCookie(signInEmailInput.value.trim());
            });
            signInEmailInput.addEventListener('blur', function () {
                setLastAuthEmailCookie(signInEmailInput.value.trim());
            });
        }

        if (forgotPasswordLink && signInEmailInput) {
            forgotPasswordLink.addEventListener('click', function () {
                setLastAuthEmailCookie(signInEmailInput.value.trim());
            });
        }

        // Toggle password visibility
        document.querySelectorAll(".password-toggle").forEach(icon => {
            icon.addEventListener("click", () => {
                const input = document.getElementById(icon.dataset.target);
                input.type = input.type === "password" ? "text" : "password";
                icon.classList.toggle("bi-eye");
                icon.classList.toggle("bi-eye-slash");
            });
        });
        const termsCheck = document.getElementById("termsCheck");
        const termsNotice = document.getElementById("termsNotice");
        signUpForm.addEventListener("submit", function(e) {
            const passwordValue = passwordInput.value;
            const termsChecked = termsCheck.checked;
            const isPasswordValid = passwordRegex.test(passwordValue);
            let preventSubmit = false;

            // Optional: hide warning immediately when user checks the box
            termsCheck.addEventListener("change", function() {
                if (termsCheck.checked) {
                    termsNotice.classList.add("d-none");
                }
            });
            if (!isPasswordValid) {
                passwordNotice.textContent = "Cannot proceed: Password must be at least 8 characters, include uppercase, lowercase, and a special character.";
                passwordNotice.classList.remove("text-success");
                passwordNotice.classList.add("text-warning");
                preventSubmit = true;
            } else {
                passwordNotice.textContent = "Password is strong!";
                passwordNotice.classList.remove("text-warning");
                passwordNotice.classList.add("text-success");
            }
            if (!termsChecked) {
                termsNotice.textContent = "Cannot proceed: You must accept the Terms & Conditions to sign up!";
                termsNotice.classList.remove("d-none");
                preventSubmit = true;
            } else {
                termsNotice.classList.add("d-none");
            }
            if (preventSubmit) {
                e.preventDefault();
            }
        });
        // Optional: hide warning immediately when user checks the box 
        termsCheck.addEventListener("change", function() {
            if (termsCheck.checked) {
                termsNotice.classList.add("d-none");
            }
        });

        // ==================== BAKE & TAKE LOGIN ====================
        const bakeAndTakeLoginBtn = document.getElementById('bakeAndTakeLoginBtn');
        const bakeAndTakeError = document.getElementById('bakeAndTakeError');
        const bakeAndTakeLoading = document.getElementById('bakeAndTakeLoading');

        bakeAndTakeLoginBtn.addEventListener('click', async function() {
            const email = document.getElementById('signInEmail').value.trim();
            const password = document.getElementById('signInPassword').value;

            // Validate inputs
            if (!email || !password) {
                bakeAndTakeError.textContent = 'Please enter your Bake & Take email and password.';
                bakeAndTakeError.classList.remove('d-none');
                return;
            }

            // Hide previous errors and show loading
            bakeAndTakeError.classList.add('d-none');
            bakeAndTakeLoading.classList.remove('d-none');
            bakeAndTakeLoginBtn.disabled = true;

            try {
                const response = await fetch('api/bakeandtake_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    // Get the redirect URL from query params or use default
                    const urlParams = new URLSearchParams(window.location.search);
                    let redirectUrl = urlParams.get('next') || data.redirect || 'index.php';
                    
                    // Security: prevent redirect to admin paths for normal users
                    if (redirectUrl.toLowerCase().includes('admin/')) {
                        redirectUrl = 'index.php';
                    }
                    
                    window.location.href = redirectUrl;
                } else {
                    bakeAndTakeError.textContent = data.message || 'Login failed. Please check your credentials.';
                    bakeAndTakeError.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Bake & Take login error:', error);
                bakeAndTakeError.textContent = 'Unable to connect to Bake & Take. Please try again later.';
                bakeAndTakeError.classList.remove('d-none');
            } finally {
                bakeAndTakeLoading.classList.add('d-none');
                bakeAndTakeLoginBtn.disabled = false;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>