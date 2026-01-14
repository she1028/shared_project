<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
require_once "../connect.php";

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../adminsignin.php");
    exit();
}

// Handle Delete action
if (isset($_POST['delete_otp_id'])) {
    $deleteId = $conn->real_escape_string($_POST['delete_otp_id']);
    $conn->query("DELETE FROM otp_requests WHERE otp_id='$deleteId'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OTP Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="bg-blur"></div>

    <div class="container admin-wrapper my-4">
        <div class="row">
            <div class="col-12">
                <div class="card-admin">
                    <h2>OTP Requests</h2>
                    <a href="dashboard.php" class="btn btn-primary mb-3">Back to Dashboard</a>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>OTP ID</th>
                                    <th>Phone</th>
                                    <th>Booking Ref</th>
                                    <th>Used</th>
                                    <th>Expires At</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM otp_requests ORDER BY created_at DESC");
                                while ($row = $res->fetch_assoc()) {
                                    $used = $row['used'] ? 'Yes' : 'No';
                                    echo "<tr>
                                        <td>{$row['otp_id']}</td>
                                        <td>{$row['phone']}</td>
                                        <td>{$row['booking_ref']}</td>
                                        <td>$used</td>
                                        <td>{$row['expires_at']}</td>
                                        <td>{$row['created_at']}</td>
                                        <td>
                                            <form method='POST' class='d-inline'>
                                                <input type='hidden' name='delete_otp_id' value='{$row['otp_id']}'>
                                                <button type='submit' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this OTP?')\">Delete</button>
                                            </form>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div> <!-- table-responsive -->
                </div> <!-- card-admin -->
            </div> <!-- col-12 -->
        </div> <!-- row -->
    </div> <!-- container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
