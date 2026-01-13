<?php
require_once __DIR__ . '/connect.php';

// Validate token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $errorMessage = 'Invalid or expired reset link.';
}

$token = $_GET['token'] ?? '';
$token_hash = $token ? hash('sha256', $token) : '';

// Find token
$sql = "SELECT reset_token_expires_at 
        FROM users 
        WHERE reset_token_hash = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();

if (empty($errorMessage) && (!$user || strtotime($user['reset_token_expires_at']) < time())) {
    $errorMessage = 'Invalid or expired reset link.';
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="container py-5">

<?php if (!empty($errorMessage)): ?>
    <div class="modal fade" id="invalidTokenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><?= htmlspecialchars($errorMessage) ?></p>
                </div>
                <div class="modal-footer">
                    <a href="forgot_password.php" class="btn btn-primary">Back</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('invalidTokenModal');
            var modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            modal.show();
        });
    </script>
</body>
</html>
<?php exit; ?>
<?php endif; ?>

    <h2 class="mb-4">Reset Password</h2>

    <form method="post" action="process_reset.php" class="col-md-6">

        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="position-relative">
                <input type="password" id="newPassword" name="password" class="form-control" required>
                <i class="bi bi-eye-slash password-toggle" data-target="newPassword" style="cursor:pointer; position:absolute; right:12px; top:10px"></i>
            </div>
            <div id="passwordNotice" class="form-text text-warning mt-1">Password must be at least 8 characters, include uppercase, lowercase, and a special character.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="position-relative">
                <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required>
                <i class="bi bi-eye-slash password-toggle" data-target="confirmPassword" style="cursor:pointer; position:absolute; right:12px; top:10px"></i>
            </div>
            <div id="confirmNotice" class="form-text text-warning mt-1 d-none">Passwords do not match.</div>
        </div>

        <button class="btn btn-dark">Reset Password</button>
    </form>

    <script>
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const passwordNotice = document.getElementById('passwordNotice');
        const confirmNotice = document.getElementById('confirmNotice');
        const resetForm = document.querySelector('form[action="process_reset.php"]');
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

        // Show live password strength
        newPassword.addEventListener('input', () => {
            const isValid = passwordRegex.test(newPassword.value);
            passwordNotice.textContent = isValid ? 'Password is strong!' : 'Password must be at least 8 characters, include uppercase, lowercase, and a special character.';
            passwordNotice.classList.toggle('text-success', isValid);
            passwordNotice.classList.toggle('text-warning', !isValid);
        });

        // Confirm password match
        confirmPassword.addEventListener('input', () => {
            const match = newPassword.value === confirmPassword.value;
            confirmNotice.classList.toggle('d-none', match);
        });

        // Prevent submit if invalid
        resetForm.addEventListener('submit', function(e) {
            const isValid = passwordRegex.test(newPassword.value);
            const match = newPassword.value === confirmPassword.value;
            if (!isValid) {
                passwordNotice.textContent = 'Cannot proceed: Password must be at least 8 characters, include uppercase, lowercase, and a special character.';
                passwordNotice.classList.remove('text-success');
                passwordNotice.classList.add('text-warning');
                e.preventDefault();
                return;
            }
            if (!match) {
                confirmNotice.textContent = 'Cannot proceed: Passwords do not match.';
                confirmNotice.classList.remove('d-none');
                e.preventDefault();
                return;
            }
        });

        // Toggle visibility for password fields
        document.querySelectorAll('.password-toggle').forEach(icon => {
            icon.addEventListener('click', () => {
                const input = document.getElementById(icon.dataset.target);
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        });
    </script>

</body>
</html>
