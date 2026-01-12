<?php
session_start();
include("../connect.php");

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

$categories = [
    'wedding' => 'Wedding',
    'children' => 'Children Party',
    'debut' => 'Debut',
    'corporate' => 'Corporate',
];

function normalizeSlug($value)
{
    $value = trim($value);
    $value = preg_replace('/\s+/', '-', $value);
    $value = strtolower($value);
    return $value;
}

function getPackagesCategoryDir($category)
{
    $safe = preg_replace('/[^a-z]/', '', strtolower($category));
    $dir = "../images/packages/" . $safe . "/";
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function buildPackagesImageIndex($category)
{
    $index = [];
    $safeCategory = preg_replace('/[^a-z]/', '', strtolower($category));

    $paths = [
        realpath(__DIR__ . '/../images/packages/' . $safeCategory),
        realpath(__DIR__ . '/../images/packages'),
    ];

    foreach ($paths as $dir) {
        if ($dir === false) continue;
        $files = scandir($dir);
        if ($files === false) continue;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $full = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($full)) continue;

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;

            $base = strtolower(pathinfo($file, PATHINFO_FILENAME));
            $rel = 'images/packages/' . (basename($dir) === 'packages' ? '' : (basename($dir) . '/')) . $file;
            if (!isset($index[$base])) {
                $index[$base] = $rel;
            }
        }
    }

    return $index;
}

function safeImageExists($relativePath)
{
    $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
    return $relativePath !== '' && file_exists(__DIR__ . '/../' . $relativePath);
}

$error_message = "";

// ==============================
// Handle Add Package
// ==============================
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $category = $_POST['category'] ?? '';
    $slug = normalizeSlug($_POST['slug'] ?? '');
    $package_title = trim($_POST['package_title'] ?? '');
    $package_name = trim($_POST['package_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $starts_at = isset($_POST['starts_at']) ? floatval($_POST['starts_at']) : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!array_key_exists($category, $categories)) {
        $error_message = "Invalid category.";
    } elseif ($slug === '' || $package_title === '' || $package_name === '' || $description === '') {
        $error_message = "Please fill in all required fields.";
    } else {
        $image_path = trim($_POST['image_path'] ?? '');

        if (!empty($_FILES['image_file']['name'])) {
            $target_dir = getPackagesCategoryDir($category);

            $filename = basename($_FILES['image_file']['name']);
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                $image_path = "images/packages/" . preg_replace('/[^a-z]/', '', strtolower($category)) . "/" . $filename;
            }
        }

        // Auto-pick image based on slug if empty
        if ($image_path === '') {
            $imageIndex = buildPackagesImageIndex($category);
            if (isset($imageIndex[$slug])) {
                $image_path = $imageIndex[$slug];
            }
        }

        try {
            $stmt = $conn->prepare("INSERT INTO packages (category, slug, package_title, package_name, description, note, starts_at, image_path, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdsi", $category, $slug, $package_title, $package_name, $description, $note, $starts_at, $image_path, $is_active);
            $stmt->execute();
            $stmt->close();

            header("Location: packages.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $error_message = "Failed to add package: " . $e->getMessage();
        }
    }
}

// ==============================
// Handle Edit Package
// ==============================
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $category = $_POST['category'] ?? '';
    $slug = normalizeSlug($_POST['slug'] ?? '');
    $package_title = trim($_POST['package_title'] ?? '');
    $package_name = trim($_POST['package_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $starts_at = isset($_POST['starts_at']) ? floatval($_POST['starts_at']) : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0) {
        $error_message = "Invalid package ID.";
    } elseif (!array_key_exists($category, $categories)) {
        $error_message = "Invalid category.";
    } elseif ($slug === '' || $package_title === '' || $package_name === '' || $description === '') {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            $current = $conn->query("SELECT image_path FROM packages WHERE id=" . intval($id))->fetch_assoc();
            $image_path = $current['image_path'] ?? '';

            $image_path_input = trim($_POST['image_path'] ?? '');
            if ($image_path_input !== '') {
                $image_path = $image_path_input;
            }

            if (!empty($_FILES['image_file']['name'])) {
                $target_dir = getPackagesCategoryDir($category);

                $filename = basename($_FILES['image_file']['name']);
                $target_file = $target_dir . $filename;

                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                    $image_path = "images/packages/" . preg_replace('/[^a-z]/', '', strtolower($category)) . "/" . $filename;
                }
            }

            $stmt = $conn->prepare("UPDATE packages SET category=?, slug=?, package_title=?, package_name=?, description=?, note=?, starts_at=?, image_path=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssssssdsii", $category, $slug, $package_title, $package_name, $description, $note, $starts_at, $image_path, $is_active, $id);
            $stmt->execute();
            $stmt->close();

            header("Location: packages.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $error_message = "Failed to update package: " . $e->getMessage();
        }
    }
}

// ==============================
// Fix image paths in DB based on category folders
// ==============================
if (isset($_POST['action']) && $_POST['action'] === 'fix_images') {
    try {
        $res = $conn->query("SELECT id, category, slug, image_path FROM packages");
        while ($row = $res->fetch_assoc()) {
            $id = intval($row['id']);
            $category = $row['category'] ?? '';
            $slug = normalizeSlug($row['slug'] ?? '');
            if ($id <= 0 || $slug === '' || $category === '') continue;

            $index = buildPackagesImageIndex($category);
            if (!isset($index[$slug])) continue;
            $correct = $index[$slug];

            $current = trim($row['image_path'] ?? '');
            $needsUpdate = ($current === '') || (!safeImageExists($current)) || ($current !== $correct);
            if ($needsUpdate) {
                $stmt = $conn->prepare("UPDATE packages SET image_path=? WHERE id=?");
                $stmt->bind_param("si", $correct, $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        header("Location: packages.php?fixed=1");
        exit();
    } catch (mysqli_sql_exception $e) {
        $error_message = "Failed to fix image paths: " . $e->getMessage();
    }
}

// ==============================
// Handle Delete Package
// ==============================
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM packages WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error_message = "Failed to delete package: " . $e->getMessage();
        }
    }

    header("Location: packages.php");
    exit();
}

// ==============================
// Fetch Packages
// ==============================
$categoryFilter = $_GET['category'] ?? '';
if ($categoryFilter !== '' && !array_key_exists($categoryFilter, $categories)) {
    $categoryFilter = '';
}

$packages = [];
try {
    if ($categoryFilter) {
        $safeCategory = mysqli_real_escape_string($conn, $categoryFilter);
        $result = $conn->query("SELECT * FROM packages WHERE category='{$safeCategory}' ORDER BY created_at DESC");
    } else {
        $result = $conn->query("SELECT * FROM packages ORDER BY category ASC, created_at DESC");
    }

    while ($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Packages table not found or query failed. Make sure you created the packages table in the ymzm database.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Packages Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>

<body>
    <div class="bg-blur"></div>

    <div class="container mt-4">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card-admin">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <h2 class="mb-2">Packages Management</h2>
                        <div class="d-flex gap-2">
                            <form method="POST" class="m-0">
                                <input type="hidden" name="action" value="fix_images">
                                
                            </form>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                                <i class="bi bi-plus-circle"></i> Add Package
                            </button>
                        </div>
                    </div>

                   

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <form method="GET" id="categoryFilterForm" class="mb-3 d-flex align-items-center gap-2">
                        <label for="category" class="form-label mb-0">Filter:</label>
                        <select name="category" id="category" class="form-select" style="width:auto;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $categoryFilter === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Slug</th>
                                    <th>Title</th>
                                    <th>Name</th>
                                    <th>Starts At</th>
                                    <th>Active</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($packages as $p): ?>
                                    <tr>
                                        <td><?= intval($p['id']) ?></td>
                                        <td><?= htmlspecialchars($p['category']) ?></td>
                                        <td><?= htmlspecialchars($p['slug']) ?></td>
                                        <td><?= htmlspecialchars($p['package_title']) ?></td>
                                        <td><?= htmlspecialchars($p['package_name']) ?></td>
                                        <td><?= number_format((float)$p['starts_at'], 2) ?></td>
                                        <td><?= intval($p['is_active']) ? 'Yes' : 'No' ?></td>
                                        <td>
                                            <?php if (!empty($p['image_path'])): ?>
                                                <img src="../<?= htmlspecialchars($p['image_path']) ?>" width="60" style="object-fit:cover;">
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-warning btn-sm edit-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editPackageModal"
                                                data-id="<?= intval($p['id']) ?>"
                                                data-category="<?= htmlspecialchars($p['category'], ENT_QUOTES) ?>"
                                                data-slug="<?= htmlspecialchars($p['slug'], ENT_QUOTES) ?>"
                                                data-title="<?= htmlspecialchars($p['package_title'], ENT_QUOTES) ?>"
                                                data-name="<?= htmlspecialchars($p['package_name'], ENT_QUOTES) ?>"
                                                data-description="<?= htmlspecialchars($p['description'], ENT_QUOTES) ?>"
                                                data-note="<?= htmlspecialchars($p['note'] ?? '', ENT_QUOTES) ?>"
                                                data-starts="<?= htmlspecialchars($p['starts_at'], ENT_QUOTES) ?>"
                                                data-image="<?= htmlspecialchars($p['image_path'] ?? '', ENT_QUOTES) ?>"
                                                data-active="<?= intval($p['is_active']) ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>

                                            <button class="btn btn-danger btn-sm delete-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deletePackageModal"
                                                data-id="<?= intval($p['id']) ?>"
                                                data-name="<?= htmlspecialchars($p['package_name'], ENT_QUOTES) ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (count($packages) === 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-muted">No packages found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ========================= ADD PACKAGE MODAL ======================== -->
    <div class="modal fade" id="addPackageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug (id) *</label>
                                <input type="text" name="slug" class="form-control" placeholder="e.g. standard" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Package Title *</label>
                                <input type="text" name="package_title" class="form-control" placeholder="e.g. CLASSIC OFFER" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Package Name *</label>
                                <input type="text" name="package_name" class="form-control" placeholder="e.g. Standard Package" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Starts At</label>
                                <input type="number" name="starts_at" step="0.01" class="form-control" value="0">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Image Path (optional)</label>
                                <input type="text" name="image_path" class="form-control" placeholder="images/packages/wedding/standard.jpg">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Or Upload Image (optional)</label>
                                <input type="file" name="image_file" class="form-control">
                                <div class="form-text">Uploads to images/packages/&lt;category&gt;/ and stores path automatically.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="add-active" checked>
                                    <label class="form-check-label" for="add-active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================= EDIT PACKAGE MODAL ======================== -->
    <div class="modal fade" id="editPackageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category" id="edit-category" class="form-select" required>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug (id) *</label>
                                <input type="text" name="slug" id="edit-slug" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Package Title *</label>
                                <input type="text" name="package_title" id="edit-title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Package Name *</label>
                                <input type="text" name="package_name" id="edit-name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description *</label>
                                <textarea name="description" id="edit-description" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" id="edit-note" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Starts At</label>
                                <input type="number" name="starts_at" id="edit-starts" step="0.01" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Image Path</label>
                                <input type="text" name="image_path" id="edit-image" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Or Upload New Image</label>
                                <input type="file" name="image_file" class="form-control">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit-active">
                                    <label class="form-check-label" for="edit-active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================= DELETE PACKAGE MODAL ======================== -->
    <div class="modal fade" id="deletePackageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete <strong id="delete-name"></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit category filter
        (() => {
            const form = document.getElementById('categoryFilterForm');
            if (!form) return;
            const select = form.querySelector('select[name="category"]');
            if (!select) return;
            select.addEventListener('change', () => form.submit());
        })();

        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editPackageModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;

                document.getElementById('edit-id').value = btn.getAttribute('data-id');
                document.getElementById('edit-category').value = btn.getAttribute('data-category');
                document.getElementById('edit-slug').value = btn.getAttribute('data-slug');
                document.getElementById('edit-title').value = btn.getAttribute('data-title');
                document.getElementById('edit-name').value = btn.getAttribute('data-name');
                document.getElementById('edit-description').value = btn.getAttribute('data-description');
                document.getElementById('edit-note').value = btn.getAttribute('data-note');
                document.getElementById('edit-starts').value = btn.getAttribute('data-starts');
                document.getElementById('edit-image').value = btn.getAttribute('data-image');

                const active = btn.getAttribute('data-active') === '1';
                document.getElementById('edit-active').checked = active;
            });

            const deleteModal = document.getElementById('deletePackageModal');
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                document.getElementById('delete-id').value = btn.getAttribute('data-id');
                document.getElementById('delete-name').textContent = btn.getAttribute('data-name');
            });
        });
    </script>
</body>

</html>
