<?php
session_start();
include("../connect.php");

function isValidPassword($password)
{
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $password);
}


// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

// Flags
$showSuccessModal = false;
$error_message = "";

// ====== Add Admin ======
if (isset($_POST['add_admin'])) {

    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $rawPassword = $_POST['password'];
    $role = 'admin';

    if (!isValidPassword($rawPassword)) {
        $error_message = "Password must be at least 8 characters, include uppercase, lowercase, and a special character.";
    } else {

        $password = password_hash($rawPassword, PASSWORD_DEFAULT);

        $check = mysqli_query($conn, "SELECT userId FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error_message = "Email already exists.";
        } else {
            mysqli_query(
                $conn,
                "INSERT INTO users (name, email, password, role)
                 VALUES ('$name', '$email', '$password', '$role')"
            );
            $showSuccessModal = true;
        }
    }
}


// ====== Edit Admin ======
if (isset($_POST['edit_admin'])) {

    // ✅ ALWAYS define these first
    $id = intval($_POST['userId']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    if ($id <= 0) {
        $error_message = "Invalid user ID.";
    } else {

        // If password is provided
        if (!empty($password)) {

            if (!isValidPassword($password)) {
                $error_message = "Password must be at least 8 characters and include uppercase, lowercase, and a number.";
            } else {
                $password = password_hash($password, PASSWORD_DEFAULT);

                $update = mysqli_query(
                    $conn,
                    "UPDATE users
                     SET name='$name', email='$email', password='$password'
                     WHERE userId='$id'"
                );
            }
        } else {
            // No password change
            $update = mysqli_query(
                $conn,
                "UPDATE users
                 SET name='$name', email='$email'
                 WHERE userId='$id'"
            );
        }

        if (isset($update) && $update) {
            $showSuccessModal = true;
        } elseif ($error_message === "") {
            $error_message = "Failed to update admin.";
        }
    }
}

// ====== Delete Admin ======
if (isset($_POST['delete_admin'])) {
    $id = intval($_POST['userId']);
    if ($id > 0) {
        $delete = mysqli_query($conn, "DELETE FROM users WHERE userId='$id'");
        if (!$delete) {
            $error_message = "Failed to delete user.";
        }
        // Do NOT redirect; the page will reload naturally
    }
}
// ====== Modal Reopen Flags ======
$openAddModal = false;
$openEditModalId = 0; // store userId if edit failed

if (isset($_POST['add_admin']) && $error_message != "") {
    $openAddModal = true;
}

if (isset($_POST['edit_admin']) && $error_message != "") {
    $openEditModalId = intval($_POST['userId']);
}



// Fetch users
$res = mysqli_query($conn, "
    SELECT userId, name, email, password, role, created_at 
    FROM users
");

$users = mysqli_fetch_all($res, MYSQLI_ASSOC); // array of users

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Users Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.1rem;
            color: #6c757d;
            line-height: 1;
        }

        .password-toggle:hover {
            color: #000;
        }
    </style>
</head>

<body>
    <div class="bg-blur"></div>
    <div class="container mt-5">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    <div class="container-fluid admin-wrapper d-flex justify-content-start align-items-start mt-2">
        <div class="row w-100 justify-content-center g-0">
            <div class="col-12 col-lg-10 d-flex justify-content-center">
                <div class="card card-admin shadow w-100" style="max-width: 1200px; padding: 3rem;">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

                        <h2 class="mb-0">Users Management</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <i class="bi bi-person-plus"></i> Add Admin
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Password (Hashed)</th>
                                    <th>Created At</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>

                            </thead>
                            <tbody>
                                <?php foreach ($users as $user) : ?>
                                    <tr>
                                        <td><?= $user['userId'] ?></td>
                                        <td><?= $user['name'] ?></td>
                                        <td><?= $user['email'] ?></td>

                                        <!-- Password (hashed, shortened for safety) -->
                                        <td style="max-width:220px; word-break:break-all;">
                                            <?= substr($user['password'], 0, 25) . '...' ?>
                                        </td>

                                        <!-- Created date -->
                                        <td>
                                            <?= date("M d, Y h:i A", strtotime($user['created_at'])) ?>
                                        </td>

                                        <td><?= ucfirst($user['role']) ?></td>

                                        <td>
                                            <button class="btn btn-warning btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#editAdminModal<?= $user['userId'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm mb-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteAdminModal"
                                                data-id="<?= $user['userId'] ?>"
                                                data-name="<?= htmlspecialchars($user['name']) ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>



                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Modals -->
    <?php foreach ($users as $user): ?>
        <div class="modal fade" id="editAdminModal<?= $user['userId'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content custom-modal">
                    <form method="POST">
                        <input type="hidden" name="userId" value="<?= $user['userId'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Admin</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <?php if ($openEditModalId === $user['userId'] && isset($_POST['edit_admin'])): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <div class="modal-body position-relative">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" value="<?= $user['name'] ?>" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="<?= $user['email'] ?>" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="position-relative">

                                    <input type="password"
                                        name="password"
                                        id="editAdminPassword<?= $user['userId'] ?>"
                                        class="form-control pe-5 edit-password"
                                        placeholder="Enter new password (leave blank to keep current)">
                                    <i class="bi bi-eye-slash password-toggle edit-toggle"></i>
                                </div>
                                <div id="editAdminPasswordNotice<?= $user['userId'] ?>" class="form-text text-warning mt-1">
                                    Password must be at least 8 characters, include uppercase, lowercase, and a special character.
                                    <?php if ($openEditModalId === $user['userId'] && $error_message != ""): ?>
                                        <br><span class="text-danger"><?= $error_message ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit_admin" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="addAdminForm" method="POST">
                    <div class="modal-body position-relative">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="position-relative">
                                <input type="password"
                                    name="password"
                                    id="addAdminPassword"
                                    class="form-control pe-5"
                                    placeholder="Enter password"
                                    required>
                                <i class="bi bi-eye-slash password-toggle" id="addToggle"></i>
                            </div>
                            <div id="addPasswordNotice" class="form-text text-warning mt-1">
                                Password must be at least 8 characters, include uppercase, lowercase, and a special character.
                                <?php if ($openAddModal && $error_message != ""): ?>
                                    <br><span class="text-danger"><?php echo $error_message; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAdminModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" action="">
                    <input type="hidden" name="userId" id="deleteUserId">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete <strong id="deleteUserName"></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_admin" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ===== Add Admin =====
        const addPassword = document.getElementById("addAdminPassword");
        const addNotice = document.getElementById("addPasswordNotice");
        const addForm = document.getElementById("addAdminForm");
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

        // Live validation
        addPassword.addEventListener("input", () => {
            const isValid = passwordRegex.test(addPassword.value);
            addNotice.textContent = isValid ?
                "Password is strong!" :
                "Password must be at least 8 characters, include uppercase, lowercase, and a special character.";
            addNotice.className = "form-text mt-1 " + (isValid ? "text-success" : "text-warning");
        });

        // Prevent invalid submission
        addForm.addEventListener("submit", (e) => {
            if (!passwordRegex.test(addPassword.value)) {
                e.preventDefault();
                addNotice.textContent = "Cannot proceed: Password must be at least 8 characters, include uppercase, lowercase, and a special character.";
                addNotice.className = "form-text text-danger mt-1";

                // Keep modal open
                bootstrap.Modal.getOrCreateInstance(
                    document.getElementById("addAdminModal")
                ).show();
            }
        });

        // Keep modal open on PHP validation errors
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($openAddModal): ?>
                const addModalEl = document.getElementById('addAdminModal');
                const addModal = new bootstrap.Modal(addModalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                addModal.show();
            <?php endif; ?>
        });


        // ===== Edit Admins =====
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".edit-password").forEach(input => {
                const userId = input.closest('.modal-content').querySelector('input[name="userId"]').value;
                const notice = document.getElementById(`editAdminPasswordNotice${userId}`);
                const toggle = input.closest('.position-relative').querySelector('.edit-toggle');

                // Live validation
                input.addEventListener("input", () => {
                    if (input.value === "") {
                        // No password change
                        notice.textContent = "Password unchanged.";
                        notice.classList.remove("text-warning", "text-danger", "text-success");
                        notice.classList.add("text-secondary");
                    } else {
                        const isValid = passwordRegex.test(input.value);
                        notice.textContent = isValid ?
                            "Password is strong!" :
                            "Password must be at least 8 characters, include uppercase, lowercase, and a special character.";

                        notice.classList.remove("text-warning", "text-danger", "text-success", "text-secondary");
                        notice.classList.add(isValid ? "text-success" : "text-warning");
                    }
                });

                // Prevent invalid submission (keep modal open)
                input.closest('form').addEventListener("submit", (e) => {
                    if (input.value !== "" && !passwordRegex.test(input.value)) {
                        e.preventDefault();
                        notice.textContent = "Cannot proceed: Password must be at least 8 characters, include uppercase, lowercase, and a special character";
                        notice.classList.remove("text-warning", "text-success", "text-secondary");
                        notice.classList.add("text-danger");

                        // Reopen the modal
                        bootstrap.Modal.getOrCreateInstance(
                            document.getElementById(`editAdminModal${userId}`)
                        ).show();
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($openAddModal): ?>
                // Keep Add Admin Modal open if validation fails
                const addModalEl = document.getElementById('addAdminModal');
                const addModal = new bootstrap.Modal(addModalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                addModal.show();
            <?php endif; ?>

            <?php if ($openEditModalId > 0): ?>
                // Keep Edit Admin Modal open if validation fails
                const editModalEl = document.getElementById('editAdminModal<?= $openEditModalId ?>');
                const editModal = new bootstrap.Modal(editModalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                editModal.show();
            <?php endif; ?>
        });


        var deleteModal = document.getElementById('deleteAdminModal');

        deleteModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget; // button that triggered the modal
            var userId = button.getAttribute('data-id'); // get user id
            var name = button.getAttribute('data-name'); // get user name

            // Populate hidden input and name in modal
            deleteModal.querySelector('#deleteUserId').value = userId;
            deleteModal.querySelector('#deleteUserName').textContent = name;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('click', function(e) {

            // ADD ADMIN TOGGLE
            if (e.target.id === 'addToggle') {
                const input = e.target.previousElementSibling;

                if (input.type === 'password') {
                    input.type = 'text'; // show password
                    e.target.classList.remove('bi-eye-slash');
                    e.target.classList.add('bi-eye');
                } else {
                    input.type = 'password'; // hide password
                    e.target.classList.remove('bi-eye');
                    e.target.classList.add('bi-eye-slash');
                }
            }

            // EDIT ADMIN TOGGLE
            if (e.target.classList.contains('edit-toggle')) {
                const input = e.target.closest('.position-relative').querySelector('.edit-password');

                if (input.type === 'password') {
                    input.type = 'text'; // show password
                    e.target.classList.remove('bi-eye-slash');
                    e.target.classList.add('bi-eye');
                } else {
                    input.type = 'password'; // hide password
                    e.target.classList.remove('bi-eye');
                    e.target.classList.add('bi-eye-slash');
                }
            }

        });
    </script>
</body>

</html>