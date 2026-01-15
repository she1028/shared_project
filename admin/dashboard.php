<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
include("../connect.php");

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../adminsignin.php");
    exit();
}

// Fetch example counts
$total_users = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'];
$total_food_categories = $conn->query("SELECT COUNT(*) AS cnt FROM food_categories")->fetch_assoc()['cnt'];
$total_foods = $conn->query("SELECT COUNT(*) AS cnt FROM foods")->fetch_assoc()['cnt'];

// Rentals counts (optional)
$total_rental_groups = 0;
$total_rental_items = 0;
try {
    $total_rental_groups = $conn->query("SELECT COUNT(*) AS cnt FROM rental_groups")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_rental_groups = 0;
}

try {
    $total_rental_items = $conn->query("SELECT COUNT(*) AS cnt FROM rental_items")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_rental_items = 0;
}

// Orders count (from checkout flow)
$total_orders = 0;
try {
    $total_orders = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_orders = 0;
}

// Recent orders (for quick visibility of delivery schedule)
$recentOrders = [];
try {
    $resRecent = $conn->query("SELECT id, full_name, status, event_date, delivery_time, total, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
    if ($resRecent) {
        while ($row = $resRecent->fetch_assoc()) {
            $recentOrders[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    $recentOrders = [];
}

// Notifications count (client-facing)
$total_notifications = 0;
$total_unread_notifications = 0;
try {
    $total_notifications = $conn->query("SELECT COUNT(*) AS cnt FROM notifications")->fetch_assoc()['cnt'];
    $total_unread_notifications = $conn->query("SELECT COUNT(*) AS cnt FROM notifications WHERE is_read = 0")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_notifications = 0;
    $total_unread_notifications = 0;
}

// SMS booking confirmations (bookings / OTP / SMS logs)
$total_bookings = 0;
$total_bookings_pending = 0;
$total_bookings_confirmed = 0;
$total_otp_requests = 0;
$total_sms_logs = 0;
try {
    $total_bookings = $conn->query("SELECT COUNT(*) AS cnt FROM bookings")->fetch_assoc()['cnt'];
    $total_bookings_pending = $conn->query("SELECT COUNT(*) AS cnt FROM bookings WHERE booking_status = 'PENDING'")->fetch_assoc()['cnt'];
    $total_bookings_confirmed = $conn->query("SELECT COUNT(*) AS cnt FROM bookings WHERE booking_status = 'CONFIRMED'")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_bookings = 0;
    $total_bookings_pending = 0;
    $total_bookings_confirmed = 0;
}

try {
    $total_otp_requests = $conn->query("SELECT COUNT(*) AS cnt FROM otp_requests")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_otp_requests = 0;
}

try {
    $total_sms_logs = $conn->query("SELECT COUNT(*) AS cnt FROM sms_logs")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_sms_logs = 0;
}

// Footer settings count (optional)
$total_footer_settings = 0;
try {
    $total_footer_settings = $conn->query("SELECT COUNT(*) AS cnt FROM footer_settings")->fetch_assoc()['cnt'];
} catch (mysqli_sql_exception $e) {
    $total_footer_settings = 0;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"> <!-- external CSS -->
</head>

<body>
    <div class="bg-blur"></div>

    <div class="container admin-wrapper d-flex justify-content-start align-items-start py-5">

        <!-- Top Row with Logout -->
        <div class="row w-100 mb-4">
            <div class="col-12 d-flex justify-content-end">
                <!-- Trigger Modal -->
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    Logout
                </button>
            </div>
        </div>

        <!-- Logout Confirmation Modal -->
        <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content custom-modal">
                    <div class="modal-header">
                        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                        <!-- Use simple btn-close without extra classes -->
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to logout?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>


        <div class="row w-100 justify-content-center g-4">

            <!-- Main Welcome Card -->
            <div class="col-12 d-flex justify-content-center">
                <div class="card card-admin shadow w-100 text-center" style="max-width: 1000px;">
                    <h4>Welcome, <?php echo $_SESSION['name']; ?></h4>
                </div>
            </div>

            <div class="row">
                <!-- Total Users Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Total Users</h5>
                        <h2 class="mb-3"><?php echo $total_users; ?></h2>
                        <a href="users.php" class="btn btn-primary btn-sm">Manage Users</a>
                    </div>
                </div>

                <!-- Total Foods Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Total Food Categories</h5>
                        <h2 class="mb-3"><?php echo $total_food_categories; ?></h2>
                        <a href="food_categories.php" class="btn btn-primary btn-sm">Manage Food Categories</a>
                    </div>
                </div>
                <!-- Total Foods Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Total Foods</h5>
                        <h2 class="mb-3"><?php echo $total_foods; ?></h2>
                        <a href="food.php" class="btn btn-primary btn-sm">Manage Foods</a>
                    </div>
                </div>

                <!-- Rentals Groups Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Rental Groups</h5>
                        <h2 class="mb-3"><?php echo $total_rental_groups; ?></h2>
                        <a href="rentals.php" class="btn btn-primary btn-sm">Manage Rentals</a>
                    </div>
                </div>

                <!-- Rentals Items Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Rental Items</h5>
                        <h2 class="mb-3"><?php echo $total_rental_items; ?></h2>
                        <a href="rentals.php?group_id=8" class="btn btn-primary btn-sm">Manage Rentals</a>
                    </div>
                </div>

                <!-- Orders Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Orders</h5>
                        <h2 class="mb-3"><?php echo $total_orders; ?></h2>
                        <a href="orders.php" class="btn btn-primary btn-sm">View Orders</a>
                    </div>
                </div>

                <!-- Notifications Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Notifications</h5>
                        <h2 class="mb-1"><?php echo $total_notifications; ?></h2>
                        <div class="small text-muted mb-3">Unread: <?php echo $total_unread_notifications; ?></div>
                        <a href="notifications.php" class="btn btn-primary btn-sm">Manage Notifications</a>
                    </div>
                </div>

                <!-- Footer Settings Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Footer</h5>
                        <h2 class="mb-3"><?php echo $total_footer_settings; ?></h2>
                        <a href="footer_settings.php" class="btn btn-primary btn-sm">Manage Footer</a>
                    </div>
                </div>

                <!-- Bookings (SMS) Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Bookings (SMS)</h5>
                        <h2 class="mb-1"><?php echo $total_bookings; ?></h2>
                        <div class="small text-muted mb-3">Pending: <?php echo $total_bookings_pending; ?> · Confirmed: <?php echo $total_bookings_confirmed; ?></div>
                        <a href="bookings.php" class="btn btn-primary btn-sm">View Bookings</a>
                    </div>
                </div>

                <!-- OTP Requests Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">OTP Requests</h5>
                        <h2 class="mb-3"><?php echo $total_otp_requests; ?></h2>
                        <a href="otp_request.php" class="btn btn-primary btn-sm">View OTP Requests</a>
                    </div>
                </div>

                <!-- SMS Logs Card -->
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">SMS Logs</h5>
                        <h2 class="mb-3"><?php echo $total_sms_logs; ?></h2>
                        <a href="sms_log.php" class="btn btn-primary btn-sm">View SMS Logs</a>
                    </div>
                </div>

            </div>

            <!-- Recent Orders -->
            <div class="col-12 d-flex justify-content-center">
                <div class="card card-admin shadow w-100 m-3" style="max-width: 1000px;">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="m-0">Recent Orders</h5>
                            <a href="orders.php" class="btn btn-outline-primary btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentOrders)): ?>
                                    <?php foreach ($recentOrders as $o): ?>
                                        <tr>
                                            <td><?php echo (int)$o['id']; ?></td>
                                            <td><?php echo htmlspecialchars((string)$o['full_name']); ?></td>
                                            <td>
                                                <?php
                                                $scheduleParts = [];
                                                if (!empty($o['event_date'])) {
                                                    $scheduleParts[] = date('M d, Y', strtotime($o['event_date']));
                                                }
                                                if (!empty($o['delivery_time'])) {
                                                    $scheduleParts[] = date('h:i A', strtotime($o['delivery_time']));
                                                }
                                                echo !empty($scheduleParts) ? htmlspecialchars(implode(' • ', $scheduleParts)) : '<span class="text-muted">—</span>';
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars((string)$o['status']); ?></td>
                                            <td class="text-end">₱<?php echo number_format((float)$o['total'], 2); ?></td>
                                            <td><?php echo !empty($o['created_at']) ? date('M d, Y h:i A', strtotime($o['created_at'])) : '—'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted">No orders yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>