<?php
session_start();
include("../connect.php");

function isValidPassword($password)
{
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
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
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $rawPassword = $_POST['password'];
    $role = 'admin';
    $rawPassword = $_POST['password'];

    if (!isValidPassword($rawPassword)) {
        $error_message = "Password must be at least 8 characters and include uppercase, lowercase, and a number.";
        return;
    }

    $password = password_hash($rawPassword, PASSWORD_DEFAULT);



    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error_message = "Email already exists.";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')");
        if ($insert) {
            $showSuccessModal = true;
        } else {
            $error_message = "Failed to add admin.";
        }
    }
}


// ====== Edit Admin ======
if (isset($_POST['edit_admin'])) {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $password = $_POST['password'];

    if (!empty($password) && !isValidPassword($password)) {
        $error_message = "Password must be at least 8 characters and include uppercase, lowercase, and a number.";
        return;
    }

    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $update = mysqli_query($conn, "UPDATE users SET name='$name', email='$email', password='$password' WHERE id='$id'");
    } else {
        $update = mysqli_query($conn, "UPDATE users SET name='$name', email='$email' WHERE id='$id'");
    }

    if ($update) {
        $showSuccessModal = true;
    } else {
        $error_message = "Failed to update admin.";
    }
}


// ====== Delete Admin ======
if (isset($_POST['delete_admin'])) {
    $id = intval($_POST['id']);
    if ($id > 0) {
        $delete = mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
        if (!$delete) {
            $error_message = "Failed to delete user.";
        }
        // Do NOT redirect; the page will reload naturally
    }
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
    <div class="container mt-4">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    <div class="container admin-wrapper d-flex justify-content-center align-items-start mt-2">
        <div class="row w-100 justify-content-center g-4">
            <div class="col-12 col-lg-10 d-flex justify-content-center">
                <div class="card card-admin shadow w-100" style="max-width: 1200px; padding: 3rem;">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

                        <h2 class="mb-0">Users Management</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <i class="bi bi-person-plus"></i> Add Admin
                        </button>
                    </div>

                    <?php if ($error_message != ""): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

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
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="userId" value="<?= $user['userId'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Admin</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
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
                                <label class="form-label">Password (leave blank to keep)</label>

                                <div class="position-relative">
                                    <input type="password" class="form-control pe-5 edit-password" name="password">
                                    <i class="bi bi-eye-slash password-toggle edit-toggle"></i>
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
                <form method="POST">
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
                                <input type="password" class="form-control pe-5" name="password">
                                <i class="bi bi-eye-slash password-toggle" id="addToggle"></i>
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
            <div class="modal-content">
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
        var deleteModal = document.getElementById('deleteAdminModal');

        deleteModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget; // button that triggered the modal
            var userId = button.getAttribute('data-userId'); // get user id
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