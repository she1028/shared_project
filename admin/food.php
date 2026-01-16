<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
include("../connect.php");


// Only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../adminsignin.php");
    exit();
}

// Fetch all foods
$categoryFilter = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? $_GET['category_id'] : '';

if ($categoryFilter) {
    $foods = $conn->query("SELECT f.*, c.food_category_name as category_name, c.food_category_id FROM foods f 
                           JOIN food_categories c ON f.food_category_id = c.food_category_id 
                           WHERE f.food_category_id = '$categoryFilter'
                           ORDER BY f.food_name ASC");
} else {
    $foods = $conn->query("SELECT f.*, c.food_category_name as category_name, c.food_category_id FROM foods f 
                           JOIN food_categories c ON f.food_category_id = c.food_category_id 
                           ORDER BY f.food_name ASC");
}



// Fetch categories dynamically for dropdowns
$categories = $conn->query("SELECT * FROM food_categories ORDER BY food_category_name ASC");
$categoriesArray = [];
while ($c = $categories->fetch_assoc()) $categoriesArray[] = $c;

// ==================== ADD FOOD ====================
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $food_id = $_POST['food_id'];
    $food_name = $_POST['food_name'];
    $food_category_id = $_POST['food_category_id'];
    $food_description = $_POST['food_description'];
    $food_price = $_POST['food_price'];
    $food_serving_size = $_POST['food_serving_size'];
    $food_is_available = isset($_POST['food_is_available']) ? 1 : 0;
    $category_short_description = trim($_POST['category_short_description'] ?? '');

    $food_image = '';

    if (!empty($_FILES['food_image']['name']) && $_FILES['food_image']['tmp_name'] != '') {
        $category_folder = $food_category_id;
        $upload_dir = "../images/food/$category_folder/";
        $web_path = "images/food/$category_folder/";

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                die("Failed to create folder $upload_dir. Check permissions.");
            }
        }

        $originalName = pathinfo($_FILES['food_image']['name'], PATHINFO_FILENAME);
        $extension = pathinfo($_FILES['food_image']['name'], PATHINFO_EXTENSION);
        $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9_-]/", "_", $originalName) . "." . $extension;
        $targetPath = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['food_image']['tmp_name'], $targetPath)) {
            $food_image = $web_path . $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO foods (food_id, food_category_id, food_name, food_description, food_price, food_serving_size, food_is_available, food_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssdis", // 8 placeholders
        $food_id,
        $food_category_id,
        $food_name,
        $food_description,
        $food_price,        // double
        $food_serving_size, // string
        $food_is_available, // integer
        $food_image         // string
    );

    $stmt->execute();

    if ($category_short_description !== '') {
        $upd = $conn->prepare("UPDATE food_categories SET food_category_description=? WHERE food_category_id=?");
        $upd->bind_param("ss", $category_short_description, $food_category_id);
        $upd->execute();
        $upd->close();
    }

    header("Location: food.php");
    exit();
}

// ==================== EDIT FOOD ====================
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $food_id = $_POST['food_id'];
    $food_name = $_POST['food_name'];
    $food_category_id = $_POST['food_category_id'];
    $food_description = $_POST['food_description'];
    $food_price = $_POST['food_price'];
    $food_serving_size = $_POST['food_serving_size'];
    $food_is_available = isset($_POST['food_is_available']) ? 1 : 0;
    $category_short_description = trim($_POST['category_short_description'] ?? '');

    // Get current image
    $current = $conn->query("SELECT food_image FROM foods WHERE food_id='$food_id'")->fetch_assoc();
    $food_image = $current['food_image'];

    if (!empty($_FILES['food_image']['name']) && $_FILES['food_image']['tmp_name'] != '') {
        $category_folder = $food_category_id;
        $upload_dir = "../images/food/$category_folder/";
        $web_path = "images/food/$category_folder/";

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                die("Failed to create folder $upload_dir. Check permissions.");
            }
        }

        $originalName = pathinfo($_FILES['food_image']['name'], PATHINFO_FILENAME);
        $extension = pathinfo($_FILES['food_image']['name'], PATHINFO_EXTENSION);
        $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9_-]/", "_", $originalName) . "." . $extension;
        $targetPath = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['food_image']['tmp_name'], $targetPath)) {
            $food_image = $web_path . $filename;
        }
    }

    $stmt = $conn->prepare("UPDATE foods SET food_category_id=?, food_name=?, food_description=?, food_price=?, food_serving_size=?, food_is_available=?, food_image=? WHERE food_id=?");
    $stmt->bind_param(
        "sssdsiss", // 8 placeholders
        $food_category_id,
        $food_name,
        $food_description,
        $food_price,        // double
        $food_serving_size, // string
        $food_is_available, // integer
        $food_image,        // string
        $food_id            // string for WHERE
    );

    $stmt->execute();

    if ($category_short_description !== '') {
        $upd = $conn->prepare("UPDATE food_categories SET food_category_description=? WHERE food_category_id=?");
        $upd->bind_param("ss", $category_short_description, $food_category_id);
        $upd->execute();
        $upd->close();
    }

    header("Location: food.php");
    exit();
}



// Handle Delete Food
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $food_id = $_POST['food_id'];
    $stmt = $conn->prepare("DELETE FROM foods WHERE food_id=?");
    $stmt->bind_param("s", $food_id);
    $stmt->execute();
    header("Location: food.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Food Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Keep wide tables inside the card and allow horizontal scrolling */
        .table-scroll-x {
            overflow-x: auto;
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        .table-scroll-x table {
            min-width: 1100px;
        }

        .food-desc {
            max-width: 300px;
            white-space: normal;
            overflow-wrap: anywhere;
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
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card card-admin shadow w-100" style="max-width: 1200px; padding: 3rem;">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                        <h2>Food Dashboard</h2>
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addFoodModal">Add New Food</button>

                        <form method="GET" id="categoryFilterForm" class="mb-3 d-flex align-items-center">
                            <label for="filter-category" class="me-2">Filter by Category:</label>
                            <select name="category_id" id="filter-category" class="form-select" style="width:auto;">
                                <option value="">All Categories</option>
                                <?php foreach ($categoriesArray as $cat): ?>
                                    <option value="<?= $cat['food_category_id'] ?>" <?= isset($_GET['category_id']) && $_GET['category_id'] == $cat['food_category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['food_category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>

                        <div class="w-100 mt-3 table-responsive table-scroll-x">
                        <table class="table table-hover text-center align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Food ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Serving Size</th>
                                    <th>Available</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($f = $foods->fetch_assoc()): ?>
                                    <tr>
                                        <!-- Food ID -->
                                        <td><?= htmlspecialchars($f['food_id']) ?></td>

                                        <!-- Image -->
                                        <td>
                                            <?php if ($f['food_image']): ?>
                                                <img src="<?= '../' . $f['food_image'] ?>" alt="<?= htmlspecialchars($f['food_name']) ?>" style="width:80px; height:80px; object-fit:cover; border-radius:8px;">
                                            <?php else: ?>
                                                <span>No Image</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Name -->
                                        <td><?= htmlspecialchars($f['food_name']) ?></td>

                                        <!-- Category -->
                                        <td><?= htmlspecialchars($f['category_name']) ?></td>

                                        <!-- Description -->
                                        <td class="food-desc"><?= htmlspecialchars($f['food_description']) ?></td>

                                        <!-- Price -->
                                        <td>₱ <?= number_format($f['food_price'], 2) ?></td>

                                        <!-- Serving Size -->
                                        <td><?= htmlspecialchars($f['food_serving_size']) ?></td>

                                        <!-- Available -->
                                        <td><?= $f['food_is_available'] ? 'Yes' : 'No' ?></td>

                                        <!-- Actions -->
                                        <td>
                                            <button class="btn btn-warning btn-sm edit-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editFoodModal"
                                                data-id="<?= $f['food_id'] ?>"
                                                data-name="<?= htmlspecialchars($f['food_name'], ENT_QUOTES) ?>"
                                                data-category="<?= $f['food_category_id'] ?>"
                                                data-description="<?= htmlspecialchars($f['food_description'], ENT_QUOTES) ?>"
                                                data-price="<?= $f['food_price'] ?>"
                                                data-serving="<?= htmlspecialchars($f['food_serving_size'], ENT_QUOTES) ?>"
                                                data-available="<?= $f['food_is_available'] ?>"
                                                data-image="<?= $f['food_image'] ?>">Edit</button>

                                            <button class="btn btn-danger btn-sm delete-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteFoodModal"
                                                data-id="<?= $f['food_id'] ?>"
                                                data-name="<?= htmlspecialchars($f['food_name'], ENT_QUOTES) ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div class="modal fade" id="addFoodModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content custom-modal">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Food</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label>Food ID</label>
                                <input type="text" name="food_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="food_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Category</label>
                                <select name="food_category_id" id="add-category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categoriesArray as $cat): ?>
                                        <option value="<?= $cat['food_category_id'] ?>"><?= htmlspecialchars($cat['food_category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Category Short Description</label>
                                <textarea name="category_short_description" id="add-category-description" class="form-control" rows="2" placeholder="Short blurb shown under this category"></textarea>
                                <small class="text-muted">Saved to the selected category.</small>
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="food_description" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Price</label>
                                <input type="number" step="0.01" name="food_price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Serving Size</label>
                                <input type="text" name="food_serving_size" class="form-control">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="food_is_available" class="form-check-input" checked>
                                <label class="form-check-label">Available</label>
                            </div>
                            <div class="mb-3">
                                <label>Image</label>
                                <input type="file" name="food_image" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Add Food</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Modal -->
        <div class="modal fade" id="editFoodModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content custom-modal">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Food</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="food_id" id="edit-food-id">

                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="food_name" id="edit-name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Category</label>
                                <select name="food_category_id" id="edit-category" class="form-control" required>
                                    <?php foreach ($categoriesArray as $cat): ?>
                                        <option value="<?= $cat['food_category_id'] ?>"><?= htmlspecialchars($cat['food_category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Category Short Description</label>
                                <textarea name="category_short_description" id="edit-category-description" class="form-control" rows="2" placeholder="Short blurb shown under this category"></textarea>
                                <small class="text-muted">Saved to the selected category.</small>
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="food_description" id="edit-description" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Price</label>
                                <input type="number" step="0.01" name="food_price" id="edit-price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Serving Size</label>
                                <input type="text" name="food_serving_size" id="edit-serving" class="form-control">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="food_is_available" id="edit-available" class="form-check-input">
                                <label class="form-check-label">Available</label>
                            </div>
                            <div class="mb-3">
                                <label>Image</label>
                                <input type="file" name="food_image" class="form-control">
                                <img id="edit-image-preview" src="" width="100" class="mt-2">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteFoodModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content custom-modal">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Food</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete <strong id="delete-food-name"></strong>?</p>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="food_id" id="delete-food-id">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-danger">Delete</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit & Delete modals  -->
        <script>
            const categoryMeta = <?php echo json_encode(array_column($categoriesArray, null, 'food_category_id')); ?>;

            document.addEventListener('DOMContentLoaded', function() {
                var editModal = document.getElementById('editFoodModal');
                var editImageInput = editModal.querySelector('input[name="food_image"]');
                var editImagePreview = document.getElementById('edit-image-preview');
                var addCategorySelect = document.getElementById('add-category');
                var addCategoryDesc = document.getElementById('add-category-description');
                var editCategorySelect = document.getElementById('edit-category');
                var editCategoryDesc = document.getElementById('edit-category-description');

                function syncCategoryDescription(selectEl, textareaEl) {
                    if (!selectEl || !textareaEl) return;
                    var meta = categoryMeta[selectEl.value];
                    textareaEl.value = meta && meta.food_category_description ? meta.food_category_description : '';
                }

                if (addCategorySelect && addCategoryDesc) {
                    addCategorySelect.addEventListener('change', function() {
                        syncCategoryDescription(addCategorySelect, addCategoryDesc);
                    });
                    syncCategoryDescription(addCategorySelect, addCategoryDesc);
                }

                editModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    document.getElementById('edit-food-id').value = button.getAttribute('data-id');
                    document.getElementById('edit-name').value = button.getAttribute('data-name');
                    document.getElementById('edit-category').value = button.getAttribute('data-category');
                    document.getElementById('edit-description').value = button.getAttribute('data-description');
                    document.getElementById('edit-price').value = button.getAttribute('data-price');
                    document.getElementById('edit-serving').value = button.getAttribute('data-serving');
                    document.getElementById('edit-available').checked = button.getAttribute('data-available') == "1";
                    editImagePreview.src = "../" + button.getAttribute('data-image');

                    syncCategoryDescription(editCategorySelect, editCategoryDesc);
                });

                if (editCategorySelect && editCategoryDesc) {
                    editCategorySelect.addEventListener('change', function() {
                        syncCategoryDescription(editCategorySelect, editCategoryDesc);
                    });
                }

                // Update preview when a new file is selected
                editImageInput.addEventListener('change', function(event) {
                    var file = this.files[0];
                    if (file) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            editImagePreview.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });

                var deleteModal = document.getElementById('deleteFoodModal');
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    document.getElementById('delete-food-id').value = button.getAttribute('data-id');
                    document.getElementById('delete-food-name').textContent = button.getAttribute('data-name');
                });

                document.getElementById('filter-category').addEventListener('change', function() {
                    document.getElementById('categoryFilterForm').submit();
                });
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>