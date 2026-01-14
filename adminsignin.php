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

// Helper: sanitize and produce safe "next" redirect (internal only)
function getSafeNextAdmin() {
    $next = null;
    if (!empty($_GET['next'])) $next = $_GET['next'];
    elseif (!empty($_POST['next'])) $next = $_POST['next'];

    if ($next) {
        if (strpos($next, 'http://') === false && strpos($next, 'https://') === false) {
            $candidate = str_replace("\0", '', $next);
            if (strpos($candidate, '..') === false) {
                return $candidate;
            }
        }
    }
    return null;
}

// ==================== ADMIN SIGN IN ====================
if (isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $res = executeQuery("SELECT * FROM users WHERE email='$email'");
    if ($res && mysqli_num_rows($res) == 1) {
        $user = mysqli_fetch_assoc($res);

        if (($user['role'] ?? '') !== 'admin') {
            $signin_error = 'This account is not an admin.';
        } elseif (!password_verify($password, $user['password'])) {
            $signin_error = 'Incorrect password!';
        } else {
            // Normalize DB id key: accept userId or userID
            $dbUserId = null;
            if (isset($user['userId'])) $dbUserId = $user['userId'];
            elseif (isset($user['userID'])) $dbUserId = $user['userID'];
            else {
                foreach ($user as $k => $v) {
                    if (stripos($k, 'id') !== false) { $dbUserId = $v; break; }
                }
            }

            $_SESSION['userID']  = $dbUserId;
            $_SESSION['userId']  = $dbUserId;
            $_SESSION['user_id'] = $dbUserId;
            $_SESSION['name']    = $user['name'] ?? '';
            $_SESSION['role']    = 'admin';
            $_SESSION['email']   = $user['email'] ?? '';

            $safeNext = getSafeNextAdmin();
            // Admin-only: allow /admin paths only
            if ($safeNext) {
                $normalizedNext = ltrim($safeNext, '/');
                if (stripos($normalizedNext, 'admin/') !== 0) {
                    $safeNext = null;
                }
            }

            header('Location: ' . ($safeNext ?: 'admin/dashboard.php'));
            exit;
        }
    } else {
        $signin_error = 'Email not registered!';
    }
}

$currentUri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'adminsignin.php', ENT_QUOTES, 'UTF-8');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Auth</title>
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
                <h5 class="fw-bold mb-0">Admin Sign In</h5>
                <div class="small text-muted">Use admin credentials only</div>
            </div>

            <?php if (isset($signin_error)) : ?>
                <div class="alert alert-danger text-dark"><?php echo $signin_error; ?></div>
            <?php endif; ?>

            <form id="signInForm" class="auth-form" method="POST" action="">
                <input type="hidden" name="next" value="<?php echo $currentUri; ?>">

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" placeholder="Enter email" name="email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="position-relative">
                        <input type="password" id="adminPassword" class="form-control" placeholder="Enter password" name="password" required>
                        <i class="bi bi-eye-slash password-toggle" data-target="adminPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-dark w-100 py-2 mb-3">Sign In</button>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="adminsignup.php" class="text-decoration-none small">Create admin account</a>
                    <a href="auth.php" class="text-decoration-none small text-muted">Client sign in</a>
                </div>
            </form>

        </div>
    </div>

    <script>
        // match auth.php password toggle behavior
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
