<?php
session_start();
require_once "../connect.php";

$statusFilter = $_GET['status'] ?? 'all';
$where = "";

if ($statusFilter !== "all") {
    $where = "WHERE booking_status = '" . $conn->real_escape_string($statusFilter) . "'";
}

// Handle Delete action
if (isset($_POST['delete_ref'])) {
    $deleteRef = $conn->real_escape_string($_POST['delete_ref']);
    $conn->query("DELETE FROM bookings WHERE booking_ref='$deleteRef'");
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=$statusFilter");
    exit;
}

// Handle Update action
if (isset($_POST['update_ref'])) {
    $updateRef = $conn->real_escape_string($_POST['update_ref']);
    $newStatus = $conn->real_escape_string($_POST['booking_status']);
    $conn->query("UPDATE bookings SET booking_status='$newStatus' WHERE booking_ref='$updateRef'");
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=$statusFilter");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="bg-blur"></div>

    <div class="container admin-wrapper my-4">
        <div class="row">
            <div class="col-12">
                <div class="card-admin">
                    <h2>Bookings</h2>
                    <a href="dashboard.php" class="btn btn-primary mb-3">Back to Dashboard</a>

                    <!-- STATUS FILTER -->
                    <form method="GET" class="d-flex align-items-center gap-2 mb-3">
                        <label for="status" class="form-label mb-0">Filter Status:</label>
                        <select id="status" name="status" class="form-select w-auto" onchange="this.form.submit()">
                            <option value="all" <?= $statusFilter == "all" ? "selected" : "" ?>>All</option>
                            <option value="pending" <?= $statusFilter == "pending" ? "selected" : "" ?>>Pending</option>
                            <option value="confirmed" <?= $statusFilter == "confirmed" ? "selected" : "" ?>>Confirmed</option>
                            <option value="cancelled" <?= $statusFilter == "cancelled" ? "selected" : "" ?>>Cancelled</option>
                        </select>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Confirmed At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM bookings $where ORDER BY created_at DESC");
                                while ($row = $res->fetch_assoc()) {
                                    $statusClass = strtolower($row['booking_status']);
                                    $ref = $row['booking_ref'];
                                    $modalId = "editModal" . md5($ref);

                                    echo "<tr>
                                <td>{$ref}</td>
                                <td>{$row['phone']}</td>
                                <td class='status {$statusClass}'>{$row['booking_status']}</td>
                                <td>{$row['created_at']}</td>
                                <td>{$row['confirmed_at']}</td>
                                <td>
                                    <!-- Edit Button -->
                                    <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#{$modalId}'>Edit</button>
                                    <!-- Delete Button -->
                                    <form method='POST' class='d-inline'>
                                        <input type='hidden' name='delete_ref' value='{$ref}'>
                                        <button type='submit' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure?')\">Delete</button>
                                    </form>
                                </td>
                            </tr>";

                                    // Edit Modal
                                    echo "
                            <!-- Edit Modal -->
<div class='modal fade' id='{$modalId}' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <form method='POST'>
                <div class='modal-header'>
                    <h5 class='modal-title'>Edit Booking Status</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body'>
                    <input type='hidden' name='update_ref' value='{$ref}'>
                    <label>Status</label>
                    <select name='booking_status' class='form-select'>
                        <option value='pending' " . ($row['booking_status'] == 'pending' ? 'selected' : '') . ">Pending</option>
                        <option value='confirmed' " . ($row['booking_status'] == 'confirmed' ? 'selected' : '') . ">Confirmed</option>
                        <option value='cancelled' " . ($row['booking_status'] == 'cancelled' ? 'selected' : '') . ">Cancelled</option>
                    </select>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-primary'>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
";
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