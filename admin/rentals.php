<?php
session_start();
include("../connect.php");

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

function normalizeKey($value)
{
    $value = trim($value);
    $value = preg_replace('/\s+/', '-', $value);
    return strtolower($value);
}

// Save uploaded image and return relative path (images/rentals/filename)
function saveUploadedImage($fieldName, $existingPath = '')
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $uploadRoot = dirname(__DIR__) . "/images/rentals/";
    if (!is_dir($uploadRoot)) {
        mkdir($uploadRoot, 0777, true);
    }

    $original = basename($_FILES[$fieldName]['name']);
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
    $targetName = time() . '_' . $safeName;
    $targetPath = $uploadRoot . $targetName;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return "images/rentals/" . $targetName;
    }

    return $existingPath;
}

// Color name validation: letters, spaces, hyphens only
function isValidColorName($value)
{
    return preg_match('/^[A-Za-z]+(?:[\s-][A-Za-z]+)*$/', $value) === 1;
}

// Ensure color schema stores only a text color_name
function ensureRentalColorSchema($conn)
{
    try {
        // Ensure color_name is present and text
        $conn->query("ALTER TABLE rental_item_colors MODIFY color_name VARCHAR(100) NOT NULL");

        // Migrate any hex stored in color_hex into color_name when color_name is empty
        $hasHex = $conn->query("SHOW COLUMNS FROM rental_item_colors LIKE 'color_hex'");
        $hexExists = $hasHex && $hasHex->num_rows > 0;
        if ($hasHex) {
            $hasHex->close();
        }

        if ($hexExists) {
            $conn->query("UPDATE rental_item_colors SET color_name = CASE WHEN (color_name IS NULL OR color_name = '') AND color_hex IS NOT NULL THEN color_hex ELSE color_name END");
            $conn->query("ALTER TABLE rental_item_colors DROP COLUMN color_hex");
        }
    } catch (mysqli_sql_exception $e) {
        // Ignore schema errors to avoid blocking page rendering
    }
}

ensureRentalColorSchema($conn);

$error_message = "";
$viewGroupId = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// ------------------------------
// Actions
// ------------------------------
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Add group
    if ($action === 'add_group') {
        $group_key = normalizeKey($_POST['group_key'] ?? '');
        $group_name = trim($_POST['group_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cover_img = trim($_POST['cover_img'] ?? '');

        if ($group_key === '' || $group_name === '') {
            $error_message = "Group key and name are required.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO rental_groups (group_key, group_name, description, cover_img) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $group_key, $group_name, $description, $cover_img);
                $stmt->execute();
                $stmt->close();
                header("Location: rentals.php");
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Save failed: " . $e->getMessage();
            }
        }
    }

    // Edit group
    if ($action === 'edit_group') {
        $id = intval($_POST['id'] ?? 0);
        $group_key = normalizeKey($_POST['group_key'] ?? '');
        $group_name = trim($_POST['group_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cover_img = trim($_POST['cover_img'] ?? '');

        if ($id <= 0 || $group_key === '' || $group_name === '') {
            $error_message = "Invalid group data.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE rental_groups SET group_key=?, group_name=?, description=?, cover_img=? WHERE id=?");
                $stmt->bind_param("ssssi", $group_key, $group_name, $description, $cover_img, $id);
                $stmt->execute();
                $stmt->close();
                header("Location: rentals.php");
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Update failed: " . $e->getMessage();
            }
        }
    }

    // Delete group (cascade items/colors)
    if ($action === 'delete_group') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM rental_groups WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Delete failed: " . $e->getMessage();
            }
        }
        header("Location: rentals.php");
        exit();
    }

    // Add item
    if ($action === 'add_item') {
        $item_id = trim($_POST['item_id'] ?? '');
        $group_id = intval($_POST['group_id'] ?? 0);
        $item_category = trim($_POST['item_category'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $img = saveUploadedImage('img_file', '');
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        $default_qty = isset($_POST['default_qty']) ? intval($_POST['default_qty']) : 1;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($group_id <= 0 || $item_id === '' || $item_category === '' || $name === '') {
            $error_message = "Item id, group, category, and name are required.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO rental_items (id, group_id, item_category, name, price, img, stock, default_qty, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissdsiii", $item_id, $group_id, $item_category, $name, $price, $img, $stock, $default_qty, $is_active);
                $stmt->execute();
                $stmt->close();
                header("Location: rentals.php?group_id=" . $group_id);
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Add item failed: " . $e->getMessage();
            }
        }
    }

    // Edit item
    if ($action === 'edit_item') {
        $item_id = trim($_POST['item_id'] ?? '');
        $group_id = intval($_POST['group_id'] ?? 0);
        $item_category = trim($_POST['item_category'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $current_img = trim($_POST['current_img'] ?? '');
        $img = saveUploadedImage('img_file', $current_img);
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        $default_qty = isset($_POST['default_qty']) ? intval($_POST['default_qty']) : 1;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $color_id = intval($_POST['color_id'] ?? 0);
        $color_name = trim($_POST['color_name'] ?? '');

        if ($item_id === '' || $group_id <= 0 || $item_category === '' || $name === '') {
            $error_message = "Invalid item data.";
        } elseif ($color_name !== '' && !isValidColorName($color_name)) {
            $error_message = "Color must be text only (letters, spaces, hyphens).";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE rental_items SET group_id=?, item_category=?, name=?, price=?, img=?, stock=?, default_qty=?, is_active=? WHERE id=?");
                $stmt->bind_param("issdsiiis", $group_id, $item_category, $name, $price, $img, $stock, $default_qty, $is_active, $item_id);
                $stmt->execute();
                $stmt->close();

                // Update or insert single editable color entry
                if ($color_name !== '') {
                    if ($color_id > 0) {
                        $stmt = $conn->prepare("UPDATE rental_item_colors SET color_name=? WHERE id=?");
                        $stmt->bind_param("si", $color_name, $color_id);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO rental_item_colors (item_id, color_name) VALUES (?, ?)");
                        $stmt->bind_param("ss", $item_id, $color_name);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                header("Location: rentals.php?group_id=" . $group_id);
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Update item failed: " . $e->getMessage();
            }
        }
    }

    // Delete item (cascade colors)
    if ($action === 'delete_item') {
        $item_id = trim($_POST['item_id'] ?? '');
        $group_id = intval($_POST['group_id'] ?? 0);
        if ($item_id !== '') {
            try {
                $stmt = $conn->prepare("DELETE FROM rental_items WHERE id=?");
                $stmt->bind_param("s", $item_id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Delete item failed: " . $e->getMessage();
            }
        }
        header("Location: rentals.php?group_id=" . $group_id);
        exit();
    }

    // Add color (not exposed in UI, kept for compatibility)
    if ($action === 'add_color') {
        $item_id = trim($_POST['item_id'] ?? '');
        $color_name = trim($_POST['color_name'] ?? '');
        $group_id = intval($_POST['group_id'] ?? 0);

        if ($item_id === '' || $color_name === '') {
            $error_message = "Color name is required.";
        } elseif (!isValidColorName($color_name)) {
            $error_message = "Color must be text only (letters, spaces, hyphens).";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO rental_item_colors (item_id, color_name) VALUES (?, ?)");
                $stmt->bind_param("ss", $item_id, $color_name);
                $stmt->execute();
                $stmt->close();
                header("Location: rentals.php?group_id=" . $group_id);
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Add color failed: " . $e->getMessage();
            }
        }
    }

    // Delete color (still allowed for cleanup)
    if ($action === 'delete_color') {
        $color_id = intval($_POST['color_id'] ?? 0);
        $group_id = intval($_POST['group_id'] ?? 0);
        if ($color_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM rental_item_colors WHERE id=?");
                $stmt->bind_param("i", $color_id);
                $stmt->execute();
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Delete color failed: " . $e->getMessage();
            }
        }
        header("Location: rentals.php?group_id=" . $group_id);
        exit();
    }
}

// ------------------------------
// Fetch data for view
// ------------------------------
$groups = [];
try {
    $res = $conn->query("SELECT g.*, (SELECT COUNT(*) FROM rental_items ri WHERE ri.group_id = g.id) AS item_count FROM rental_groups g ORDER BY g.id DESC");
    while ($row = $res->fetch_assoc()) {
        $groups[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Query failed: " . $e->getMessage();
}

$items = [];
$colorsByItem = [];
$selectedGroup = null;

if ($viewGroupId > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM rental_groups WHERE id=?");
        $stmt->bind_param("i", $viewGroupId);
        $stmt->execute();
        $res = $stmt->get_result();
        $selectedGroup = $res->fetch_assoc();
        $stmt->close();

        if ($selectedGroup) {
            $stmt = $conn->prepare("SELECT * FROM rental_items WHERE group_id=? ORDER BY item_category ASC, name ASC");
            $stmt->bind_param("i", $viewGroupId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $items[] = $row;
            }
            $stmt->close();

            if (!empty($items)) {
                $ids = array_map(function ($it) {
                    return $it['id'];
                }, $items);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $types = str_repeat('s', count($ids));
                $stmt = $conn->prepare("SELECT id, item_id, color_name FROM rental_item_colors WHERE item_id IN ($placeholders) ORDER BY id ASC");
                $stmt->bind_param($types, ...$ids);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $colorsByItem[$row['item_id']][] = $row;
                }
                $stmt->close();
            }
        } else {
            $error_message = "Group not found.";
        }
    } catch (mysqli_sql_exception $e) {
        $error_message = "Query failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentals Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>

<body>
    <div class="bg-blur"></div>

    <div class="container mt-4 d-flex align-items-center gap-2 flex-wrap">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <?php if ($viewGroupId > 0): ?>
            <a href="rentals.php" class="btn btn-outline-secondary">All Groups</a>
        <?php endif; ?>
    </div>

    <div class="container-fluid py-4">
        <div class="card-admin" style="max-width: 1500px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h2 class="mb-2">Rentals Management</h2>
                <?php if ($viewGroupId <= 0): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                        <i class="bi bi-plus-circle"></i> Add Group
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($viewGroupId > 0 && $selectedGroup): ?>
                <div class="row g-3">
                    <div class="col-xl-4 col-lg-12">
                        <div class="p-3 rounded" style="background: rgba(255,255,255,0.06);">
                            <h5 class="mb-1"><?php echo htmlspecialchars($selectedGroup['group_name']); ?></h5>
                            <div class="text-muted mb-2">
                                Key: <strong><?php echo htmlspecialchars($selectedGroup['group_key']); ?></strong>
                            </div>
                            <?php if (!empty($selectedGroup['description'])): ?>
                                <div class="mb-2"><?php echo nl2br(htmlspecialchars($selectedGroup['description'])); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($selectedGroup['cover_img'])): ?>
                                <img src="../<?php echo htmlspecialchars($selectedGroup['cover_img']); ?>" width="200" style="object-fit:cover;border-radius:10px;">
                            <?php else: ?>
                                <div class="text-muted">No cover image set.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-xl-8 col-12 ">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <h5 class="mb-0">Items</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="bi bi-plus-circle"></i> Add Item
                            </button>
                        </div>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover align-middle text-center">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Category</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Default Qty</th>
                                        <th>Available</th>
                                        <th>Image</th>
                                        <th>Colors</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $it): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($it['id']); ?></td>
                                            <td><?php echo htmlspecialchars($it['item_category']); ?></td>
                                            <td><?php echo htmlspecialchars($it['name']); ?></td>
                                            <td><?php echo number_format((float)$it['price'], 2); ?></td>
                                            <td><?php echo intval($it['stock']); ?></td>
                                            <td><?php echo intval($it['default_qty']); ?></td>
                                            <td><?php echo intval($it['is_active']) ? 'Yes' : 'No'; ?></td>
                                            <td>
                                                <?php if (!empty($it['img'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($it['img']); ?>" width="60" style="object-fit:cover;">
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-start" style="min-width:160px;">
                                                <?php $colors = $colorsByItem[$it['id']] ?? []; ?>
                                                <?php if (empty($colors)): ?>
                                                    <span class="text-muted">None</span>
                                                <?php else: ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($colors as $c): ?>
                                                            <span class="badge bg-secondary text-dark" style="background: rgba(255,255,255,0.85); color:#000;">&nbsp;<?php echo htmlspecialchars($c['color_name']); ?>&nbsp;</span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php $colors = $colorsByItem[$it['id']] ?? []; $firstColor = $colors[0] ?? null; ?>
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal"
                                                    data-item-id="<?php echo htmlspecialchars($it['id'], ENT_QUOTES); ?>"
                                                    data-group-id="<?php echo intval($it['group_id']); ?>"
                                                    data-category="<?php echo htmlspecialchars($it['item_category'], ENT_QUOTES); ?>"
                                                    data-name="<?php echo htmlspecialchars($it['name'], ENT_QUOTES); ?>"
                                                    data-price="<?php echo htmlspecialchars($it['price'], ENT_QUOTES); ?>"
                                                    data-img="<?php echo htmlspecialchars($it['img'], ENT_QUOTES); ?>"
                                                    data-stock="<?php echo intval($it['stock']); ?>"
                                                    data-default-qty="<?php echo intval($it['default_qty']); ?>"
                                                    data-active="<?php echo intval($it['is_active']); ?>"
                                                    data-color-id="<?php echo $firstColor ? intval($firstColor['id']) : 0; ?>"
                                                    data-color-name="<?php echo $firstColor ? htmlspecialchars($firstColor['color_name'], ENT_QUOTES) : ''; ?>"
                                                    data-colors='<?php echo htmlspecialchars(json_encode(array_values($colors)), ENT_QUOTES); ?>'>
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#deleteItemModal"
                                                    data-item-id="<?php echo htmlspecialchars($it['id'], ENT_QUOTES); ?>"
                                                    data-group-id="<?php echo intval($it['group_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($it['name'], ENT_QUOTES); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (count($items) === 0): ?>
                                        <tr>
                                            <td colspan="10" class="text-muted">No items yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Key</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Cover</th>
                                <th>Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $g): ?>
                                <tr>
                                    <td><?php echo intval($g['id']); ?></td>
                                    <td><?php echo htmlspecialchars($g['group_key']); ?></td>
                                    <td><?php echo htmlspecialchars($g['group_name']); ?></td>
                                    <td class="text-start" style="max-width:280px;">
                                        <?php echo $g['description'] ? nl2br(htmlspecialchars($g['description'])) : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($g['cover_img'])): ?>
                                            <img src="../<?php echo htmlspecialchars($g['cover_img']); ?>" width="70" style="object-fit:cover;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo intval($g['item_count']); ?></td>
                                    <td>
                                        <a class="btn btn-success btn-sm" href="rentals.php?group_id=<?php echo intval($g['id']); ?>">
                                            <i class="bi bi-box-seam"></i> View Items
                                        </a>
                                        <button class="btn btn-warning btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#editGroupModal"
                                            data-id="<?php echo intval($g['id']); ?>"
                                            data-key="<?php echo htmlspecialchars($g['group_key'], ENT_QUOTES); ?>"
                                            data-name="<?php echo htmlspecialchars($g['group_name'], ENT_QUOTES); ?>"
                                            data-description="<?php echo htmlspecialchars($g['description'] ?? '', ENT_QUOTES); ?>"
                                            data-cover="<?php echo htmlspecialchars($g['cover_img'] ?? '', ENT_QUOTES); ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#deleteGroupModal"
                                            data-id="<?php echo intval($g['id']); ?>"
                                            data-name="<?php echo htmlspecialchars($g['group_name'], ENT_QUOTES); ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($groups) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-muted">No rental groups found. Import db_rentals_seed.txt or add one.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="addGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST">
                    <input type="hidden" name="action" value="add_group">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Group Key *</label>
                                <input type="text" name="group_key" class="form-control" placeholder="chairs" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Group Name *</label>
                                <input type="text" name="group_name" class="form-control" placeholder="Chairs" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cover Image Path</label>
                                <input type="text" name="cover_img" class="form-control" placeholder="images/Catering/chair/c1.jpg">
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

    <div class="modal fade" id="editGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_group">
                    <input type="hidden" name="id" id="edit-group-id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Group Key *</label>
                                <input type="text" name="group_key" id="edit-group-key" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Group Name *</label>
                                <input type="text" name="group_name" id="edit-group-name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="edit-group-description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cover Image Path</label>
                                <input type="text" name="cover_img" id="edit-group-cover" class="form-control">
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

    <div class="modal fade" id="deleteGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST">
                    <input type="hidden" name="action" value="delete_group">
                    <input type="hidden" name="id" id="delete-group-id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Deleting the group will also remove its items and colors. Continue deleting <strong id="delete-group-name"></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_item">
                    <input type="hidden" name="group_id" value="<?php echo $viewGroupId; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Item ID *</label>
                                <input type="text" name="item_id" class="form-control" placeholder="chr-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <input type="text" name="item_category" class="form-control" placeholder="Chairs" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="Kids Chair" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price *</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload Image</label>
                                <input type="file" name="img_file" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" class="form-control" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Default Qty</label>
                                <input type="number" name="default_qty" class="form-control" value="1">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="add-item-active" checked>
                                    <label class="form-check-label" for="add-item-active">Available</label>
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

    <div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_item">
                    <input type="hidden" name="group_id" id="edit-item-group-id">
                    <input type="hidden" name="item_id" id="edit-item-id">
                    <input type="hidden" name="current_img" id="edit-item-current-img">
                    <input type="hidden" name="color_id" id="edit-color-id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Item ID</label>
                                <input type="text" class="form-control" id="edit-item-id-display" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category *</label>
                                <input type="text" name="item_category" id="edit-item-category" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price *</label>
                                <input type="number" step="0.01" name="price" id="edit-item-price" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" id="edit-item-name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload New Image</label>
                                <input type="file" name="img_file" id="edit-item-file" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6 d-flex flex-column">
                                <label class="form-label">Current Image</label>
                                <img id="edit-item-preview" src="" alt="Preview" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #555;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" id="edit-item-stock" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Default Qty</label>
                                <input type="number" name="default_qty" id="edit-item-default-qty" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Add Color</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="add-color-name" name="color_name" form="add-color-form" placeholder="Add new color" pattern="^[A-Za-z]+(?:[\s-][A-Za-z]+)*$" title="Letters, spaces, and hyphens only">
                                    <button class="btn btn-outline-primary" type="submit" form="add-color-form">Add Color</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-1">Current Colors:</div>
                                <div id="edit-color-list" class="d-flex flex-wrap gap-1"></div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit-item-active">
                                    <label class="form-check-label" for="edit-item-active">Available</label>
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

    <form id="add-color-form" method="POST" class="d-none">
        <input type="hidden" name="action" value="add_color">
        <input type="hidden" name="item_id" id="add-color-item-id">
        <input type="hidden" name="group_id" id="add-color-group-id">
    </form>

    <form id="delete-color-form" method="POST" class="d-none">
        <input type="hidden" name="action" value="delete_color">
        <input type="hidden" name="color_id" id="delete-color-id">
        <input type="hidden" name="group_id" id="delete-color-group-id">
    </form>

    <!-- Add color modal removed; color is managed inside Edit Item -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editGroupModal = document.getElementById('editGroupModal');
        if (editGroupModal) {
            editGroupModal.addEventListener('show.bs.modal', event => {
                const btn = event.relatedTarget;
                document.getElementById('edit-group-id').value = btn.getAttribute('data-id');
                document.getElementById('edit-group-key').value = btn.getAttribute('data-key');
                document.getElementById('edit-group-name').value = btn.getAttribute('data-name');
                document.getElementById('edit-group-description').value = btn.getAttribute('data-description');
                document.getElementById('edit-group-cover').value = btn.getAttribute('data-cover');
            });
        }

        const deleteGroupModal = document.getElementById('deleteGroupModal');
        if (deleteGroupModal) {
            deleteGroupModal.addEventListener('show.bs.modal', event => {
                const btn = event.relatedTarget;
                document.getElementById('delete-group-id').value = btn.getAttribute('data-id');
                document.getElementById('delete-group-name').textContent = btn.getAttribute('data-name');
            });
        }

        const editItemModal = document.getElementById('editItemModal');
        if (editItemModal) {
            editItemModal.addEventListener('show.bs.modal', event => {
                const btn = event.relatedTarget;
                const itemId = btn.getAttribute('data-item-id');
                const imgPath = btn.getAttribute('data-img') || '';
                document.getElementById('edit-item-id').value = itemId;
                document.getElementById('edit-item-id-display').value = itemId;
                document.getElementById('edit-item-group-id').value = btn.getAttribute('data-group-id');
                document.getElementById('add-color-item-id').value = itemId;
                document.getElementById('add-color-group-id').value = btn.getAttribute('data-group-id');
                document.getElementById('edit-item-category').value = btn.getAttribute('data-category');
                document.getElementById('edit-item-name').value = btn.getAttribute('data-name');
                document.getElementById('edit-item-price').value = btn.getAttribute('data-price');
                document.getElementById('edit-item-stock').value = btn.getAttribute('data-stock');
                document.getElementById('edit-item-default-qty').value = btn.getAttribute('data-default-qty');
                document.getElementById('edit-item-current-img').value = imgPath;
                const active = btn.getAttribute('data-active') === '1';
                document.getElementById('edit-item-active').checked = active;

                const preview = document.getElementById('edit-item-preview');
                if (imgPath) {
                    preview.src = '../' + imgPath;
                } else {
                    preview.src = 'https://via.placeholder.com/120?text=No+Image';
                }

                document.getElementById('edit-color-id').value = btn.getAttribute('data-color-id') || 0;

                // Populate color badges
                const colorList = document.getElementById('edit-color-list');
                const colorsJson = btn.getAttribute('data-colors') || '[]';
                let colors;
                try {
                    colors = JSON.parse(colorsJson);
                } catch (e) {
                    colors = [];
                }
                colorList.innerHTML = '';
                if (colors.length === 0) {
                    const span = document.createElement('span');
                    span.className = 'text-muted';
                    span.textContent = 'None';
                    colorList.appendChild(span);
                } else {
                    colors.forEach(c => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'd-flex align-items-center gap-1';

                        const badge = document.createElement('span');
                        badge.className = 'badge bg-secondary text-dark';
                        badge.style.background = 'rgba(255,255,255,0.85)';
                        badge.style.color = '#000';
                        badge.textContent = c.color_name;

                        const delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 'btn btn-sm btn-danger';
                        delBtn.innerHTML = '&times;';
                        delBtn.style.lineHeight = '1';
                        delBtn.onclick = () => {
                            if (confirm(`Delete color "${c.color_name}"?`)) {
                                deleteColor(c.id);
                            }
                        };

                        wrapper.appendChild(badge);
                        wrapper.appendChild(delBtn);
                        colorList.appendChild(wrapper);
                    });
                }
            });
        }

        const addColorInput = document.getElementById('add-color-name');
        const addColorForm = document.getElementById('add-color-form');
        if (addColorInput && addColorForm) {
            addColorForm.addEventListener('submit', () => {
                // form attribute already ties the input to the form
            });
        }

        const editItemFileInput = document.getElementById('edit-item-file');
        if (editItemFileInput) {
            editItemFileInput.addEventListener('change', () => {
                const file = editItemFileInput.files[0];
                const preview = document.getElementById('edit-item-preview');
                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        const deleteItemModal = document.getElementById('deleteItemModal');
        if (deleteItemModal) {
            deleteItemModal.addEventListener('show.bs.modal', event => {
                const btn = event.relatedTarget;
                document.getElementById('delete-item-id').value = btn.getAttribute('data-item-id');
                document.getElementById('delete-item-group-id').value = btn.getAttribute('data-group-id');
                document.getElementById('delete-item-name').textContent = btn.getAttribute('data-name');
            });
        }

        function deleteColor(colorId) {
            document.getElementById('delete-color-id').value = colorId;
            document.getElementById('delete-color-group-id').value = document.getElementById('edit-item-group-id').value;
            document.getElementById('delete-color-form').submit();
        }
    </script>
</body>

</html>
