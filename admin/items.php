<?php
session_start();
include("../connect.php");

// Only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// ==============================
// Fetch all rentals
// ==============================
$categoryFilter = isset($_GET['rent_category_id']) && $_GET['rent_category_id'] !== '' ? $_GET['rent_category_id'] : '';

if ($categoryFilter) {
    $rentals = $conn->query("
        SELECT r.*,
               c.rent_category_name,
               col.rental_color_name,
               col.rental_hex_code
        FROM rentals r
        JOIN rental_categories c
            ON r.rental_category_id = c.rent_category_id
        LEFT JOIN rental_colors col
            ON r.rental_color_id = col.rental_color_id
        WHERE r.rental_category_id = '$categoryFilter'
        ORDER BY r.rental_name ASC
    ");
} else {
    $rentals = $conn->query("
        SELECT r.*,
               c.rent_category_name,
               col.rental_color_name,
               col.rental_hex_code
        FROM rentals r
        JOIN rental_categories c
            ON r.rental_category_id = c.rent_category_id
        LEFT JOIN rental_colors col
            ON r.rental_color_id = col.rental_color_id
        ORDER BY r.rental_name ASC
    ");
}

// ==============================
// Fetch rental categories for dropdown
// ==============================
$categories = $conn->query("SELECT * FROM rental_categories ORDER BY rent_category_name ASC");
$categoriesArray = [];
while ($c = $categories->fetch_assoc()) $categoriesArray[] = $c;

// ==============================
// Fetch colors for dropdown
// ==============================
$colors = $conn->query("SELECT * FROM rental_colors ORDER BY rental_color_name ASC");

// ==============================
// Handle Add Rental
// ==============================
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $rental_id          = $_POST['rental_id'];
    $rental_category_id = $_POST['rental_category_id'];
    $rental_name        = $_POST['rental_name'];
    $rental_price       = $_POST['rental_price'];
    $rental_stock       = $_POST['rental_stock'];
    $rental_default_qty = $_POST['rental_default_qty'];
    $rental_color_id    = $_POST['rental_color_id'] ?? null;

    $image_path = '';
    if (!empty($_FILES['rental_img']['name'])) {
        $target_dir = "../images/rentals/";
        $image_path = $target_dir . basename($_FILES['rental_img']['name']);
        move_uploaded_file($_FILES['rental_img']['tmp_name'], $image_path);
    }

    $stmt = $conn->prepare("INSERT INTO rentals 
        (rental_id, rental_category_id, rental_name, rental_price, rental_img, rental_stock, rental_default_qty, rental_color_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdsiii", $rental_id, $rental_category_id, $rental_name, $rental_price, $image_path, $rental_stock, $rental_default_qty, $rental_color_id);
    $stmt->execute();
    header("Location: items.php");
    exit();
}

// ==============================
// Handle Edit Rental
// ==============================
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $rental_id          = $_POST['rental_id'];
    $rental_category_id = $_POST['rental_category_id'];
    $rental_name        = $_POST['rental_name'];
    $rental_price       = $_POST['rental_price'];
    $rental_stock       = $_POST['rental_stock'];
    $rental_default_qty = $_POST['rental_default_qty'];
    $rental_color_id    = $_POST['rental_color_id'] ?? null;

    $current = $conn->query("SELECT rental_img FROM rentals WHERE rental_id='$rental_id'")->fetch_assoc();
    $image_path = $current['rental_img'];

    if (!empty($_FILES['rental_img']['name'])) {
        $target_dir = "../images/rentals/";
        $image_path = $target_dir . basename($_FILES['rental_img']['name']);
        move_uploaded_file($_FILES['rental_img']['tmp_name'], $image_path);
    }

    $stmt = $conn->prepare("UPDATE rentals SET rental_category_id=?, rental_name=?, rental_price=?, rental_img=?, rental_stock=?, rental_default_qty=?, rental_color_id=? WHERE rental_id=?");
    $stmt->bind_param("ssdsiiis", $rental_category_id, $rental_name, $rental_price, $image_path, $rental_stock, $rental_default_qty, $rental_color_id, $rental_id);
    $stmt->execute();
    header("Location: items.php");
    exit();
}

// ==============================
// Handle Delete Rental
// ==============================
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $rental_id = $_POST['rental_id'];

    $stmt = $conn->prepare("DELETE FROM rentals WHERE rental_id=?");
    $stmt->bind_param("s", $rental_id);
    $stmt->execute();
    header("Location: items.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-blur"></div>
<div class="container mt-4">
    <a href="dashboard.php" class="btn btn-secondary">Back</a>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card-admin">
                <h2>Rental Dashboard</h2>
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addRentalModal">Add New Rental</button>

                <!-- Category Filter -->
                <form method="GET" id="categoryFilterForm" class="mb-3 d-flex align-items-center">
                    <label for="filter-category" class="me-2">Filter by Category:</label>
                    <select name="rent_category_id" id="filter-category" class="form-select" style="width:auto;">
                        <option value="">All Categories</option>
                        <?php foreach ($categoriesArray as $cat): ?>
                            <option value="<?= $cat['rent_category_id'] ?>" <?= isset($_GET['rent_category_id']) && $_GET['rent_category_id'] == $cat['rent_category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['rent_category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <!-- Color Filter (Optional) -->
                <div class="mb-3">
                    <label>Color</label>
                    <select name="color_id" class="form-select">
                        <option value="">Select Color</option>
                        <?php
                        $colors->data_seek(0); // Reset pointer
                        while ($col = $colors->fetch_assoc()):
                        ?>
                            <option value="<?= $col['rental_color_id'] ?>">
                                <?= htmlspecialchars($col['rental_color_name']) ?> (<?= htmlspecialchars($col['rental_hex_code']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Rentals Table -->
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Color ID</th>
                            <th>Color</th>
                            <th>Hex</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Default Qty</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $rentals->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['rental_id']) ?></td>
                                <td><?= htmlspecialchars($r['rental_name']) ?></td>
                                <td><?= htmlspecialchars($r['rent_category_name']) ?></td>
                                <td><?= htmlspecialchars($r['rental_color_id'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['rental_color_name'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($r['rental_hex_code'])): ?>
                                        <span style="display:inline-block;width:18px;height:18px;background:<?= htmlspecialchars($r['rental_hex_code']) ?>;border:1px solid #000;"></span>
                                        <?= htmlspecialchars($r['rental_hex_code']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($r['rental_price'], 2) ?></td>
                                <td><?= $r['rental_stock'] ?></td>
                                <td><?= $r['rental_default_qty'] ?></td>
                                <td><?php if ($r['rental_img']): ?><img src="<?= $r['rental_img'] ?>" width="50"><?php endif; ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRentalModal"
                                        data-id="<?= $r['rental_id'] ?>"
                                        data-name="<?= htmlspecialchars($r['rental_name'], ENT_QUOTES) ?>"
                                        data-category="<?= $r['rental_category_id'] ?>"
                                        data-price="<?= $r['rental_price'] ?>"
                                        data-stock="<?= $r['rental_stock'] ?>"
                                        data-default="<?= $r['rental_default_qty'] ?>"
                                        data-color="<?= $r['rental_color_id'] ?>"
                                        data-image="<?= $r['rental_img'] ?>">Edit</button>

                                    <button class="btn btn-danger btn-sm delete-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteRentalModal"
                                        data-id="<?= $r['rental_id'] ?>"
                                        data-name="<?= htmlspecialchars($r['rental_name'], ENT_QUOTES) ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================= ADD RENTAL MODAL ======================== -->
<div class="modal fade" id="addRentalModal" tabindex="-1" aria-labelledby="addRentalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRentalLabel">Add New Rental</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Rental ID</label>
                        <input type="text" name="rental_id" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Category</label>
                        <select name="rental_category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categoriesArray as $cat): ?>
                                <option value="<?= $cat['rent_category_id'] ?>"><?= htmlspecialchars($cat['rent_category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Color</label>
                        <select name="rental_color_id" class="form-select">
                            <option value="">Select Color</option>
                            <?php
                            $colors->data_seek(0);
                            while ($col = $colors->fetch_assoc()):
                            ?>
                                <option value="<?= $col['rental_color_id'] ?>"><?= htmlspecialchars($col['rental_color_name']) ?> (<?= htmlspecialchars($col['rental_hex_code']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Rental Name</label>
                        <input type="text" name="rental_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Rental Price</label>
                        <input type="number" step="0.01" name="rental_price" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Stock</label>
                        <input type="number" name="rental_stock" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Default Quantity</label>
                        <input type="number" name="rental_default_qty" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Image</label>
                        <input type="file" name="rental_img" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Add Rental</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================= EDIT RENTAL MODAL ======================== -->
<div class="modal fade" id="editRentalModal" tabindex="-1" aria-labelledby="editRentalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="rental_id" id="edit-rental-id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRentalLabel">Edit Rental</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Category</label>
                        <select name="rental_category_id" id="edit-rental-category" class="form-select" required>
                            <?php foreach ($categoriesArray as $cat): ?>
                                <option value="<?= $cat['rent_category_id'] ?>"><?= htmlspecialchars($cat['rent_category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Color</label>
                        <select name="rental_color_id" id="edit-rental-color" class="form-select">
                            <option value="">Select Color</option>
                            <?php
                            $colors->data_seek(0);
                            while ($col = $colors->fetch_assoc()):
                            ?>
                                <option value="<?= $col['rental_color_id'] ?>"><?= htmlspecialchars($col['rental_color_name']) ?> (<?= htmlspecialchars($col['rental_hex_code']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Rental Name</label>
                        <input type="text" name="rental_name" id="edit-rental-name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Rental Price</label>
                        <input type="number" step="0.01" name="rental_price" id="edit-rental-price" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Stock</label>
                        <input type="number" name="rental_stock" id="edit-rental-stock" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Default Quantity</label>
                        <input type="number" name="rental_default_qty" id="edit-rental-default" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Image</label>
                        <input type="file" name="rental_img" id="edit-rental-img" class="form-control">
                        <img id="current-image" src="" width="50" class="mt-1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Update Rental</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================= DELETE RENTAL MODAL ======================== -->
<div class="modal fade" id="deleteRentalModal" tabindex="-1" aria-labelledby="deleteRentalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="rental_id" id="delete-rental-id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRentalLabel">Delete Rental</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="delete-rental-name"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Populate edit modal
    var editModal = document.getElementById('editRentalModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('edit-rental-id').value = button.getAttribute('data-id');
        document.getElementById('edit-rental-name').value = button.getAttribute('data-name');
        document.getElementById('edit-rental-category').value = button.getAttribute('data-category');
        document.getElementById('edit-rental-price').value = button.getAttribute('data-price');
        document.getElementById('edit-rental-stock').value = button.getAttribute('data-stock');
        document.getElementById('edit-rental-default').value = button.getAttribute('data-default');
        document.getElementById('edit-rental-color').value = button.getAttribute('data-color');
        document.getElementById('current-image').src = button.getAttribute('data-image');
    });

    // Populate delete modal
    var deleteModal = document.getElementById('deleteRentalModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('delete-rental-id').value = button.getAttribute('data-id');
        document.getElementById('delete-rental-name').textContent = button.getAttribute('data-name');
    });

    // Category filter
    document.getElementById('filter-category').addEventListener('change', function() {
        document.getElementById('categoryFilterForm').submit();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
