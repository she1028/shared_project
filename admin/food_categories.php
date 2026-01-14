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

// ==================== ADD CATEGORY ====================
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $category_id = $_POST['food_category_id'];
    $category_name = $_POST['food_category_name'];
    $category_description = $_POST['food_category_description'];

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO food_categories (food_category_id, food_category_name, food_category_description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $category_id, $category_name, $category_description);
    $stmt->execute();

    // Create folder for this category
    $category_folder = "../images/food/" . $category_id;
    if (!is_dir($category_folder)) {
        mkdir($category_folder, 0777, true);
    }

    header("Location: food_categories.php");
    exit();
}

// ==================== EDIT CATEGORY ====================
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $category_id = $_POST['food_category_id'];
    $category_name = $_POST['food_category_name'];
    $category_description = $_POST['food_category_description'];

    $stmt = $conn->prepare("UPDATE food_categories SET food_category_name=?, food_category_description=? WHERE food_category_id=?");
    $stmt->bind_param("sss", $category_name, $category_description, $category_id);
    $stmt->execute();

    header("Location: food_categories.php");
    exit();
}

// ==================== DELETE CATEGORY ====================
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $category_id = $_POST['food_category_id'];

    // Optional: check if foods exist under this category before deleting
    $stmt = $conn->prepare("DELETE FROM food_categories WHERE food_category_id=?");
    $stmt->bind_param("s", $category_id);
    $stmt->execute();

    header("Location: food_categories.php");
    exit();
}

// Fetch all categories
$categories = $conn->query("SELECT * FROM food_categories ORDER BY food_category_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Food Categories</title>
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
             <div class="card card-admin shadow w-100" style="max-width: 1200px; padding: 3rem;">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h2>Food Categories</h2>
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add New Category</button>
                <table class="table table-hover text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['food_category_id']) ?></td>
                            <td><?= htmlspecialchars($cat['food_category_name']) ?></td>
                            <td><?= htmlspecialchars($cat['food_category_description']) ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm edit-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editCategoryModal"
                                    data-id="<?= $cat['food_category_id'] ?>"
                                    data-name="<?= htmlspecialchars($cat['food_category_name'], ENT_QUOTES) ?>"
                                    data-description="<?= htmlspecialchars($cat['food_category_description'], ENT_QUOTES) ?>">Edit</button>

                                <button class="btn btn-danger btn-sm delete-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteCategoryModal"
                                    data-id="<?= $cat['food_category_id'] ?>"
                                    data-name="<?= htmlspecialchars($cat['food_category_name'], ENT_QUOTES) ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content custom-modal">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label>Category ID</label>
                        <input type="text" name="food_category_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="food_category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="food_category_description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Category</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content custom-modal">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="food_category_id" id="edit-category-id">

                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="food_category_name" id="edit-category-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="food_category_description" id="edit-category-description" class="form-control"></textarea>
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

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content custom-modal">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete-category-name"></strong>?</p>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="food_category_id" id="delete-category-id">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit modal
    var editModal = document.getElementById('editCategoryModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('edit-category-id').value = button.getAttribute('data-id');
        document.getElementById('edit-category-name').value = button.getAttribute('data-name');
        document.getElementById('edit-category-description').value = button.getAttribute('data-description');
    });

    // Delete modal
    var deleteModal = document.getElementById('deleteCategoryModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('delete-category-id').value = button.getAttribute('data-id');
        document.getElementById('delete-category-name').textContent = button.getAttribute('data-name');
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
