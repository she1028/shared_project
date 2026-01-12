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

$inclusionTypes = [
    'menu' => 'Menu',
    'rentals' => 'Rentals',
    'decorations' => 'Decorations',
    'services' => 'Services',
];

function normalizeSlug($value)
{
    $value = trim($value);
    $value = preg_replace('/\s+/', '-', $value);
    $value = strtolower($value);
    return $value;
}

// Ensure manual item order column exists and is populated
function ensurePackageItemOrderColumn($conn)
{
    try {
        $res = $conn->query("SHOW COLUMNS FROM package_inclusion_items LIKE 'item_order'");
        $hasItemOrder = $res && $res->num_rows > 0;
        if ($res) {
            $res->close();
        }

        if (!$hasItemOrder) {
            $conn->query("ALTER TABLE package_inclusion_items ADD COLUMN item_order INT NOT NULL DEFAULT 0");

            // Migrate existing sort_order values if that column exists
            $res2 = $conn->query("SHOW COLUMNS FROM package_inclusion_items LIKE 'sort_order'");
            if ($res2 && $res2->num_rows > 0) {
                $conn->query("UPDATE package_inclusion_items SET item_order = sort_order");
                $res2->close();
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Schema change failed; continue so the page can still render, but ordering may not work
    }
}

ensurePackageItemOrderColumn($conn);
function getPackagesCategoryDir($category)
{
    $safe = preg_replace('/[^a-z]/', '', strtolower($category));
    $dir = "../images/packages/" . $safe . "/";
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function safeImageExists($relativePath)
{
    $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
    return $relativePath !== '' && file_exists(__DIR__ . '/../' . $relativePath);
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

function isValidInclusionType($value)
{
    return in_array($value, ['menu', 'rentals', 'decorations', 'services'], true);
}

$error_message = "";
$success_message = "";

$detailId = isset($_GET['detail_id']) ? intval($_GET['detail_id']) : 0;

// ==============================
// Ensure tables exist (friendly error)
// ==============================
try {
    $conn->query("SELECT 1 FROM package_details LIMIT 1");
    $conn->query("SELECT 1 FROM package_inclusion_items LIMIT 1");
} catch (mysqli_sql_exception $e) {
    $error_message = "Required tables not found. Please import db_inclusions_seed.txt first (tables: package_details, package_inclusion_items).";
}

// ==============================
// Handle actions
// ==============================
if (empty($error_message) && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ------- Add / Edit package_details -------
    if ($action === 'add_detail' || $action === 'edit_detail') {
        $id = intval($_POST['id'] ?? 0);
        $category = $_POST['category'] ?? '';
        $slug = normalizeSlug($_POST['slug'] ?? '');
        $offer = trim($_POST['offer'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $price_label = trim($_POST['price_label'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if (!array_key_exists($category, $categories)) {
            $error_message = "Invalid category.";
        } elseif ($slug === '' || $offer === '' || $title === '' || $price_label === '') {
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
                if ($action === 'add_detail') {
                    $stmt = $conn->prepare("INSERT INTO package_details (category, slug, offer, title, price_label, note, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $category, $slug, $offer, $title, $price_label, $note, $image_path);
                    $stmt->execute();
                    $stmt->close();
                    header("Location: package_details.php");
                    exit();
                } else {
                    if ($id <= 0) {
                        $error_message = "Invalid detail ID.";
                    } else {
                        $stmt = $conn->prepare("UPDATE package_details SET category=?, slug=?, offer=?, title=?, price_label=?, note=?, image_path=? WHERE id=?");
                        $stmt->bind_param("sssssssi", $category, $slug, $offer, $title, $price_label, $note, $image_path, $id);
                        $stmt->execute();
                        $stmt->close();
                        header("Location: package_details.php");
                        exit();
                    }
                }
            } catch (mysqli_sql_exception $e) {
                $error_message = "Save failed: " . $e->getMessage();
            }
        }
    }

    // ------- Delete package_details (cascade deletes items) -------
    if ($action === 'delete_detail') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM package_details WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header("Location: package_details.php");
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Delete failed: " . $e->getMessage();
            }
        }
    }

    // ------- Fix image paths in package_details -------
    if ($action === 'fix_detail_images') {
        try {
            $res = $conn->query("SELECT id, category, slug, image_path FROM package_details");
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
                    $stmt = $conn->prepare("UPDATE package_details SET image_path=? WHERE id=?");
                    $stmt->bind_param("si", $correct, $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            header("Location: package_details.php?fixed=1");
            exit();
        } catch (mysqli_sql_exception $e) {
            $error_message = "Failed to fix image paths: " . $e->getMessage();
        }
    }

    // ------- Add inclusion item -------
    if ($action === 'add_item') {
        $package_detail_id = intval($_POST['package_detail_id'] ?? 0);
        $inclusion_type = $_POST['inclusion_type'] ?? '';
        $item_text = trim($_POST['item_text'] ?? '');
        $item_order = isset($_POST['item_order']) ? intval($_POST['item_order']) : 0;

        if ($package_detail_id <= 0) {
            $error_message = "Invalid package detail.";
        } elseif (!isValidInclusionType($inclusion_type)) {
            $error_message = "Invalid inclusion type.";
        } elseif ($item_text === '') {
            $error_message = "Item text is required.";
        } elseif (!is_numeric($_POST['item_order'] ?? '')) {
            $error_message = "Item order must be a number.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO package_inclusion_items (package_detail_id, inclusion_type, item_text, item_order) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $package_detail_id, $inclusion_type, $item_text, $item_order);
                $stmt->execute();
                $stmt->close();
                header("Location: package_details.php?detail_id=" . $package_detail_id);
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Add item failed: " . $e->getMessage();
            }
        }
    }

    // ------- Update inclusion item -------
    if ($action === 'update_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $package_detail_id = intval($_POST['package_detail_id'] ?? 0);
        $item_text = trim($_POST['item_text'] ?? '');
        $item_order = isset($_POST['item_order']) ? intval($_POST['item_order']) : 0;

        if ($item_id <= 0 || $package_detail_id <= 0) {
            $error_message = "Invalid item.";
        } elseif ($item_text === '') {
            $error_message = "Item text is required.";
        } elseif (!is_numeric($_POST['item_order'] ?? '')) {
            $error_message = "Item order must be a number.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE package_inclusion_items SET item_text=?, item_order=? WHERE id=? AND package_detail_id=?");
                $stmt->bind_param("siii", $item_text, $item_order, $item_id, $package_detail_id);
                $stmt->execute();
                $stmt->close();
                header("Location: package_details.php?detail_id=" . $package_detail_id);
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Update item failed: " . $e->getMessage();
            }
        }
    }

    // ------- Delete inclusion item -------
    if ($action === 'delete_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $package_detail_id = intval($_POST['package_detail_id'] ?? 0);
        if ($item_id > 0 && $package_detail_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM package_inclusion_items WHERE id=?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $stmt->close();
                header("Location: package_details.php?detail_id=" . $package_detail_id);
                exit();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Delete item failed: " . $e->getMessage();
            }
        }
    }
}

// ==============================
// Fetch data
// ==============================
$filterCategories = $categories;
if (empty($error_message)) {
    try {
        $res = $conn->query("SELECT DISTINCT category FROM package_details ORDER BY category ASC");
        if ($res) {
            $dynamic = [];
            while ($row = $res->fetch_assoc()) {
                $cat = trim((string) ($row['category'] ?? ''));
                if ($cat === '') continue;
            }

            if (!empty($dynamic)) {
                asort($dynamic);
                foreach ($dynamic as $k => $v) {
                    $filterCategories[$k] = $v;
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
    }
}

$categoryFilter = $_GET['category'] ?? '';
if ($categoryFilter !== '' && !array_key_exists($categoryFilter, $filterCategories)) {
    $categoryFilter = '';
}

$details = [];
$selectedDetail = null;
$itemsByType = [
    'menu' => [],
    'rentals' => [],
    'decorations' => [],
    'services' => [],
];

if (empty($error_message)) {
    try {
        if ($detailId > 0) {
            $stmt = $conn->prepare("SELECT * FROM package_details WHERE id=?");
            $stmt->bind_param("i", $detailId);
            $stmt->execute();
            $res = $stmt->get_result();
            $selectedDetail = $res->fetch_assoc();
            $stmt->close();

            if ($selectedDetail) {
                $stmt = $conn->prepare("SELECT * FROM package_inclusion_items WHERE package_detail_id=? ORDER BY inclusion_type ASC, item_order ASC, id ASC");
                $stmt->bind_param("i", $detailId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $t = $row['inclusion_type'];
                    if (isset($itemsByType[$t])) {
                        $itemsByType[$t][] = $row;
                    }
                }
                $stmt->close();
            } else {
                $error_message = "Package detail not found.";
            }
        } else {
            if ($categoryFilter) {
                $stmt = $conn->prepare("SELECT * FROM package_details WHERE category=? ORDER BY id DESC");
                $stmt->bind_param("s", $categoryFilter);
                $stmt->execute();
                $res = $stmt->get_result();
            } else {
                $res = $conn->query("SELECT * FROM package_details ORDER BY id DESC");
            }

            if ($categoryFilter) {
                while ($row = $res->fetch_assoc()) {
                    $details[] = $row;
                }
                $stmt->close();
            } else {
                while ($row = $res->fetch_assoc()) {
                    $details[] = $row;
                }
            }
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
    <title>Package Inclusions / Details</title>
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
        <?php if ($detailId > 0): ?>
            <a href="package_details.php" class="btn btn-outline-secondary ms-2">All Details</a>


        <?php endif; ?>
    </div>

    <div class="container py-4">
        <div class="card-admin">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h2 class="mb-2">Package Inclusions / Details</h2>
                <?php if ($detailId <= 0): ?>
                    <div class="d-flex gap-2">

                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDetailModal">
                            <i class="bi bi-plus-circle"></i> Add Detail
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['fixed']) && $_GET['fixed'] == 1): ?>
                <div class="alert alert-success">Detail image paths updated based on <strong>images/packages/&lt;category&gt;/</strong>.</div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($detailId > 0 && $selectedDetail): ?>
                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <div class="p-3 rounded" style="background: rgba(255,255,255,0.06);">
                            <h5 class="mb-1"><?php echo htmlspecialchars($selectedDetail['title']); ?></h5>
                            <div class="text-muted mb-2">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($selectedDetail['category']); ?></span>
                                <span class="ms-2">Slug: <strong><?php echo htmlspecialchars($selectedDetail['slug']); ?></strong></span>
                            </div>
                            <div><strong>Offer:</strong> <?php echo htmlspecialchars($selectedDetail['offer']); ?></div>
                            <div><strong>Price label:</strong> <?php echo htmlspecialchars($selectedDetail['price_label']); ?></div>
                            <?php if (!empty($selectedDetail['note'])): ?>
                                <div class="mt-2"><strong>Note:</strong><br><?php echo nl2br(htmlspecialchars($selectedDetail['note'])); ?></div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <?php if (!empty($selectedDetail['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($selectedDetail['image_path']); ?>" width="180" style="object-fit:cover;border-radius:10px;">
                                    <?php if (!safeImageExists($selectedDetail['image_path'])): ?>
                                        <div class="text-warning mt-2">Image file not found for this path.</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-muted">No image path set.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div class="text-muted">Inclusions</div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="inclusionFilter" class="form-label mb-0">Show:</label>
                                <select id="inclusionFilter" class="form-select form-select-sm" style="width:auto;">
                                    <option value="all">All</option>
                                    <?php foreach ($inclusionTypes as $typeKey => $typeLabel): ?>
                                        <option value="<?php echo htmlspecialchars($typeKey); ?>"><?php echo htmlspecialchars($typeLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>

                            </div>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($inclusionTypes as $typeKey => $typeLabel): ?>
                                <div class="col-12 inclusion-section" data-inclusion-type="<?php echo htmlspecialchars($typeKey); ?>">
                                    <div class="p-3 rounded" style="background: rgba(255,255,255,0.06);">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($typeLabel); ?></h5>
                                        </div>

                                        <form method="POST" class="row g-2 align-items-end mb-2">
                                            <input type="hidden" name="action" value="add_item">
                                            <input type="hidden" name="package_detail_id" value="<?php echo intval($detailId); ?>">
                                            <input type="hidden" name="inclusion_type" value="<?php echo htmlspecialchars($typeKey); ?>">

                                            <div class="col-12 col-md-8">
                                                <label class="form-label">Add item</label>
                                                <input type="text" name="item_text" class="form-control" placeholder="e.g. 3 Main courses" required>
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label">Item Number</label>
                                                <input type="number" name="item_order" class="form-control" value="1" min="0" required>
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">Add</button>
                                            </div>
                                        </form>

                                        <?php if (count($itemsByType[$typeKey]) === 0): ?>
                                            <div class="text-muted">No items.</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle text-center mb-0">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th style="width:80px;">Order</th>
                                                            <th>Item</th>
                                                            <th style="width:120px;">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($itemsByType[$typeKey] as $it): ?>
                                                            <tr>
                                                                <td><?php echo intval($it['item_order']); ?></td>
                                                                <td class="text-start"><?php echo htmlspecialchars($it['item_text']); ?></td>
                                                                <td class="d-flex flex-column gap-1 align-items-stretch">
                                                                    <button type="button" class="btn btn-warning btn-sm edit-inclusion-btn" data-bs-toggle="modal" data-bs-target="#editInclusionItemModal"
                                                                        data-item-id="<?php echo intval($it['id']); ?>"
                                                                        data-item-text="<?php echo htmlspecialchars($it['item_text'], ENT_QUOTES); ?>"
                                                                        data-item-order="<?php echo intval($it['item_order']); ?>"
                                                                        data-package-id="<?php echo intval($detailId); ?>">
                                                                        <i class="bi bi-pencil"></i> Edit
                                                                    </button>
                                                                    <form method="POST" class="m-0" onsubmit="return confirm('Delete this item?');">
                                                                        <input type="hidden" name="action" value="delete_item">
                                                                        <input type="hidden" name="item_id" value="<?php echo intval($it['id']); ?>">
                                                                        <input type="hidden" name="package_detail_id" value="<?php echo intval($detailId); ?>">
                                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                                            <i class="bi bi-trash"></i> Delete
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            <?php else: ?>

                <form method="GET" id="categoryFilterFormDetails" class="mb-3 d-flex align-items-center gap-2">
                    <label for="category" class="form-label mb-0">Filter:</label>
                    <select name="category" id="category" class="form-select" style="width:auto;">
                        <option value="">All Categories</option>
                        <?php foreach ($filterCategories as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $categoryFilter === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
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
                                <th>Offer</th>
                                <th>Title</th>
                                <th>Price</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $d): ?>
                                <tr>
                                    <td><?php echo intval($d['id']); ?></td>
                                    <td><?php echo htmlspecialchars($d['category']); ?></td>
                                    <td><?php echo htmlspecialchars($d['slug']); ?></td>
                                    <td><?php echo htmlspecialchars($d['offer']); ?></td>
                                    <td><?php echo htmlspecialchars($d['title']); ?></td>
                                    <td><?php echo htmlspecialchars($d['price_label']); ?></td>
                                    <td>
                                        <?php if (!empty($d['image_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($d['image_path']); ?>" width="60" style="object-fit:cover;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-info btn-sm" href="package_details.php?detail_id=<?php echo intval($d['id']); ?>">
                                            <i class="bi bi-list-check"></i> Items
                                        </a>

                                        <button class="btn btn-warning btn-sm edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editDetailModal"
                                            data-id="<?php echo intval($d['id']); ?>"
                                            data-category="<?php echo htmlspecialchars($d['category'], ENT_QUOTES); ?>"
                                            data-slug="<?php echo htmlspecialchars($d['slug'], ENT_QUOTES); ?>"
                                            data-offer="<?php echo htmlspecialchars($d['offer'], ENT_QUOTES); ?>"
                                            data-title="<?php echo htmlspecialchars($d['title'], ENT_QUOTES); ?>"
                                            data-price="<?php echo htmlspecialchars($d['price_label'], ENT_QUOTES); ?>"
                                            data-note="<?php echo htmlspecialchars($d['note'] ?? '', ENT_QUOTES); ?>"
                                            data-image="<?php echo htmlspecialchars($d['image_path'] ?? '', ENT_QUOTES); ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>

                                        <button class="btn btn-danger btn-sm delete-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteDetailModal"
                                            data-id="<?php echo intval($d['id']); ?>"
                                            data-title="<?php echo htmlspecialchars($d['title'], ENT_QUOTES); ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($details) === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-muted">No package details found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- Edit Inclusion Item Modal -->
    <div class="modal fade" id="editInclusionItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST">
                    <input type="hidden" name="action" value="update_item">
                    <input type="hidden" name="item_id" id="edit-inclusion-item-id">
                    <input type="hidden" name="package_detail_id" value="<?php echo intval($detailId); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item Number</label>
                            <input type="number" name="item_order" id="edit-inclusion-item-order" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_text" id="edit-inclusion-item-text" class="form-control" required>
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

    <!-- ========================= ADD DETAIL MODAL ======================== -->
    <div class="modal fade" id="addDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_detail">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Package Detail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug (id) *</label>
                                <input type="text" name="slug" class="form-control" placeholder="e.g. standard" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Offer *</label>
                                <input type="text" name="offer" class="form-control" placeholder="CLASSIC OFFER" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price Label *</label>
                                <input type="text" name="price_label" class="form-control" placeholder="700 per guest" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" class="form-control" placeholder="Standard Package" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image Path</label>
                                <input type="text" name="image_path" class="form-control" placeholder="images/packages/wedding/standard.jpg">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Or Upload Image</label>
                                <input type="file" name="image_file" class="form-control" accept="image/*">
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

    <!-- ========================= EDIT DETAIL MODAL ======================== -->
    <div class="modal fade" id="editDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_detail">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Package Detail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category" id="edit_category" class="form-select" required>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug (id) *</label>
                                <input type="text" name="slug" id="edit_slug" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Offer *</label>
                                <input type="text" name="offer" id="edit_offer" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price Label *</label>
                                <input type="text" name="price_label" id="edit_price" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" id="edit_title" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" id="edit_note" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image Path</label>
                                <input type="text" name="image_path" id="edit_image" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Or Upload Image</label>
                                <input type="file" name="image_file" class="form-control" accept="image/*">
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

    <!-- ========================= DELETE DETAIL MODAL ======================== -->
    <div class="modal fade" id="deleteDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST">
                    <input type="hidden" name="action" value="delete_detail">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Detail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete <strong id="delete_title"></strong>?
                        <div class="text-danger mt-2">This will also delete all inclusion items under it.</div>
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
        // Inclusion type filter (Menu/Rentals/Decorations/Services)
        (() => {
            const select = document.getElementById('inclusionFilter');
            const resetBtn = document.getElementById('inclusionReset');
            if (!select) return;

            const sections = Array.from(document.querySelectorAll('.inclusion-section'));
            const valid = new Set(['all', ...sections.map(s => s.dataset.inclusionType)]);

            const applyFilter = (value) => {
                const v = (value || 'all').toLowerCase();
                sections.forEach((el) => {
                    const t = (el.dataset.inclusionType || '').toLowerCase();
                    el.style.display = (v === 'all' || v === t) ? '' : 'none';
                });

                const url = new URL(window.location.href);
                if (v === 'all') {
                    url.searchParams.delete('inclusion');
                } else {
                    url.searchParams.set('inclusion', v);
                }
                window.history.replaceState({}, '', url);
            };

            const params = new URLSearchParams(window.location.search);
            const initial = (params.get('inclusion') || 'all').toLowerCase();
            if (valid.has(initial)) {
                select.value = initial;
                applyFilter(initial);
            } else {
                select.value = 'all';
                applyFilter('all');
            }

            select.addEventListener('change', () => applyFilter(select.value));
            if (resetBtn) resetBtn.addEventListener('click', () => {
                select.value = 'all';
                applyFilter('all');
            });
        })();

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_category').value = btn.dataset.category;
                document.getElementById('edit_slug').value = btn.dataset.slug;
                document.getElementById('edit_offer').value = btn.dataset.offer;
                document.getElementById('edit_title').value = btn.dataset.title;
                document.getElementById('edit_price').value = btn.dataset.price;
                document.getElementById('edit_note').value = btn.dataset.note;
                document.getElementById('edit_image').value = btn.dataset.image;
            });
        });

        const editInclusionModal = document.getElementById('editInclusionItemModal');
        if (editInclusionModal) {
            editInclusionModal.addEventListener('show.bs.modal', event => {
                const btn = event.relatedTarget;
                document.getElementById('edit-inclusion-item-id').value = btn.getAttribute('data-item-id');
                document.getElementById('edit-inclusion-item-order').value = btn.getAttribute('data-item-order');
                document.getElementById('edit-inclusion-item-text').value = btn.getAttribute('data-item-text');
            });
        }

        // Auto-submit category filter on list view
        (() => {
            const form = document.getElementById('categoryFilterFormDetails');
            if (!form) return;
            const select = form.querySelector('select[name="category"]');
            if (!select) return;
            select.addEventListener('change', () => form.submit());
        })();

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delete_id').value = btn.dataset.id;
                document.getElementById('delete_title').textContent = btn.dataset.title;
            });
        });
    </script>
</body>

</html>