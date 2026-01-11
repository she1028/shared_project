<?php
session_start();
include("../connect.php");

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

// Fetch example counts
$total_sections = $conn->query("SELECT COUNT(*) AS cnt FROM sections")->fetch_assoc()['cnt'];
$total_users = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'];
$total_food_categories = $conn->query("SELECT COUNT(*) AS cnt FROM food_categories")->fetch_assoc()['cnt'];
$total_foods = $conn->query("SELECT COUNT(*) AS cnt FROM foods")->fetch_assoc()['cnt'];


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

            <!-- Sections Content Card -->
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 d-flex justify-content-center">
                    <div class="card card-admin shadow text-center w-100 m-3" style="max-width: 300px;">
                        <h5 class="mb-2">Sections Content</h5>
                        <h2 class="mb-3"><?php echo $total_sections; ?></h2>
                        <a href="manage_sections.php" class="btn btn-primary btn-sm">Manage Sections</a>
                    </div>
                </div>
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

            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>