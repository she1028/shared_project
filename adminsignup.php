<?php
include("connect.php"); // $conn and executeQuery()
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}

// If already signed in as admin, go straight to dashboard
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

// ==================== ADMIN SIGN UP ====================
if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
    if (!isset($_POST['terms'])) {
        $signup_error = 'You must accept the Terms & Conditions to sign up!';
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $rawPassword = $_POST['password'];
        $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);
        $role = 'admin';

        // Check if email exists
        $check = executeQuery("SELECT * FROM users WHERE email='$email'");
        if ($check && mysqli_num_rows($check) > 0) {
            $signup_error = 'Email already exists!';
        } else {
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $email, $passwordHash, $role);
            if ($stmt->execute()) {
                $newUserId = $stmt->insert_id ?: $conn->insert_id;

                // Auto-login the newly created admin
                $_SESSION['userID']  = $newUserId;
                $_SESSION['userId']  = $newUserId;
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['name']    = $name;
                $_SESSION['role']    = $role;
                $_SESSION['email']   = $email;

                header('Location: admin/dashboard.php');
                exit;
            } else {
                $signup_error = 'Sign Up failed. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Signup</title>
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

            <div class="text-center mb-3">
                <h5 class="fw-bold mb-0">Admin Sign Up</h5>
                <div class="small text-muted">Create an admin-only account</div>
            </div>

            <?php if (isset($signup_error)) : ?>
                <div class="alert alert-danger text-dark"><?php echo $signup_error; ?></div>
            <?php endif; ?>

            <form id="signUpForm" class="auth-form" method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" placeholder="Enter name" name="name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" placeholder="Enter email" name="email" required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <div class="position-relative">
                        <input type="password" id="adminSignUpPassword" class="form-control" placeholder="Enter password" name="password" required>
                        <i class="bi bi-eye-slash password-toggle" data-target="adminSignUpPassword"></i>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" required>
                    <label class="form-check-label" for="terms">I accept the Terms & Conditions</label>
                </div>

                <button type="submit" class="btn btn-outline-light w-100 py-2">Sign Up</button>

                <div class="text-center mt-3">
                    <a href="adminsignin.php" class="text-decoration-none small">Back to Admin Sign In</a>
                </div>
            </form>

        </div>
    </div>

    <script>
        document.querySelectorAll('.password-toggle').forEach(icon => {
            icon.addEventListener('click', () => {
                const targetId = icon.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>
