<?php
session_start();
include("../connect.php");

// Only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

// Ensure table exists (matches provided seed schema)
$conn->query("CREATE TABLE IF NOT EXISTS footer_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logo_path VARCHAR(255) NOT NULL,
    business_name VARCHAR(100) NOT NULL, 
    copyright_text VARCHAR(150) NOT NULL,
    business_hours VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    catering_phone VARCHAR(50) NOT NULL,
    food_order_phone VARCHAR(50) NOT NULL,
    instagram_url VARCHAR(255),
    x_url VARCHAR(255),
    facebook_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$action = $_POST['action'] ?? '';

function saveFooterLogoUpload(string $fileField, string $fallbackLogoPath = ''): string {
    if (!isset($_FILES[$fileField]) || empty($_FILES[$fileField]['tmp_name'])) {
        return $fallbackLogoPath;
    }

    $file = $_FILES[$fileField];
    if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
        return $fallbackLogoPath;
    }

    $tmp = $file['tmp_name'];
    $info = @getimagesize($tmp);
    if ($info === false) {
        return $fallbackLogoPath;
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        // default to png if extension is missing/unknown but the file is an image
        $ext = 'png';
    }

    $uploadDir = __DIR__ . '/../images/footer/';
    $webDir = 'images/footer/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($tmp, $target)) {
        return $webDir . $filename;
    }

    return $fallbackLogoPath;
}

function postVal(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}

if ($action === 'add') {
    $logo_path = postVal('logo_path');
    $logo_path = saveFooterLogoUpload('logo_file', $logo_path);
    $business_name = postVal('business_name');
    $copyright_text = postVal('copyright_text');
    $business_hours = postVal('business_hours');
    $address = postVal('address');
    $catering_phone = postVal('catering_phone');
    $food_order_phone = postVal('food_order_phone');
    $instagram_url = postVal('instagram_url');
    $x_url = postVal('x_url');
    $facebook_url = postVal('facebook_url');

    $stmt = $conn->prepare("INSERT INTO footer_settings (
        logo_path, business_name, copyright_text, business_hours, address,
        catering_phone, food_order_phone, instagram_url, x_url, facebook_url
    ) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        'ssssssssss',
        $logo_path,
        $business_name,
        $copyright_text,
        $business_hours,
        $address,
        $catering_phone,
        $food_order_phone,
        $instagram_url,
        $x_url,
        $facebook_url
    );
    $stmt->execute();
    $stmt->close();

    header('Location: footer_settings.php');
    exit();
}

if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $logo_path = postVal('logo_path');
    $logo_path = saveFooterLogoUpload('logo_file', $logo_path);
    $business_name = postVal('business_name');
    $copyright_text = postVal('copyright_text');
    $business_hours = postVal('business_hours');
    $address = postVal('address');
    $catering_phone = postVal('catering_phone');
    $food_order_phone = postVal('food_order_phone');
    $instagram_url = postVal('instagram_url');
    $x_url = postVal('x_url');
    $facebook_url = postVal('facebook_url');

    $stmt = $conn->prepare("UPDATE footer_settings
        SET logo_path=?, business_name=?, copyright_text=?, business_hours=?, address=?,
            catering_phone=?, food_order_phone=?, instagram_url=?, x_url=?, facebook_url=?
        WHERE id=?");
    $stmt->bind_param(
        'ssssssssssi',
        $logo_path,
        $business_name,
        $copyright_text,
        $business_hours,
        $address,
        $catering_phone,
        $food_order_phone,
        $instagram_url,
        $x_url,
        $facebook_url,
        $id
    );
    $stmt->execute();
    $stmt->close();

    header('Location: footer_settings.php');
    exit();
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM footer_settings WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: footer_settings.php');
    exit();
}

$rows = [];
$res = $conn->query("SELECT * FROM footer_settings ORDER BY updated_at DESC, id DESC");
while ($res && ($r = $res->fetch_assoc())) {
    $rows[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Footer Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        .table-scroll-x { overflow-x:auto; max-width:100%; -webkit-overflow-scrolling: touch; }
        .table-scroll-x table { min-width: 1100px; }
        .cell-wrap { white-space: normal; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <div class="bg-blur"></div>
    <div class="container mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="container admin-wrapper py-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="m-0">Footer Settings</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                Add Footer Info
            </button>
        </div>

        <div class="card card-admin shadow p-3">
            <div class="table-scroll-x">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Logo</th>
                            <th>Business</th>
                            <th>Hours</th>
                            <th>Address</th>
                            <th>Phones</th>
                            <th>Social Links</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) === 0): ?>
                            <tr>
                                <td colspan="9" class="text-muted">No footer settings found. Click “Add Footer Info”.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td class="cell-wrap">
                                        <?php if (!empty($r['logo_path'])): ?>
                                            <div class="small text-muted mb-1"><?= htmlspecialchars($r['logo_path']) ?></div>
                                            <img src="<?= htmlspecialchars('../' . ltrim($r['logo_path'], '/')) ?>" alt="Logo" style="width:60px; height:60px; object-fit:contain; background:#fff; border-radius:8px;">
                                        <?php else: ?>
                                            <span class="text-muted">(none)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-wrap text-start">
                                        <div class="fw-semibold"><?= htmlspecialchars($r['business_name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($r['copyright_text']) ?></div>
                                    </td>
                                    <td class="cell-wrap text-start"><?= htmlspecialchars($r['business_hours']) ?></td>
                                    <td class="cell-wrap text-start"><?= nl2br(htmlspecialchars($r['address'])) ?></td>
                                    <td class="cell-wrap text-start">
                                        <div><span class="small text-muted">Catering:</span> <?= htmlspecialchars($r['catering_phone']) ?></div>
                                        <div><span class="small text-muted">Food order:</span> <?= htmlspecialchars($r['food_order_phone']) ?></div>
                                    </td>
                                    <td class="cell-wrap text-start">
                                        <div><span class="small text-muted">IG:</span> <?= htmlspecialchars($r['instagram_url'] ?? '') ?></div>
                                        <div><span class="small text-muted">X:</span> <?= htmlspecialchars($r['x_url'] ?? '') ?></div>
                                        <div><span class="small text-muted">FB:</span> <?= htmlspecialchars($r['facebook_url'] ?? '') ?></div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($r['updated_at']) ?></td>
                                    <td>
                                        <button
                                            class="btn btn-warning btn-sm edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-logo="<?= htmlspecialchars($r['logo_path'], ENT_QUOTES) ?>"
                                            data-business="<?= htmlspecialchars($r['business_name'], ENT_QUOTES) ?>"
                                            data-copyright="<?= htmlspecialchars($r['copyright_text'], ENT_QUOTES) ?>"
                                            data-hours="<?= htmlspecialchars($r['business_hours'], ENT_QUOTES) ?>"
                                            data-address="<?= htmlspecialchars($r['address'], ENT_QUOTES) ?>"
                                            data-catering="<?= htmlspecialchars($r['catering_phone'], ENT_QUOTES) ?>"
                                            data-food="<?= htmlspecialchars($r['food_order_phone'], ENT_QUOTES) ?>"
                                            data-ig="<?= htmlspecialchars($r['instagram_url'] ?? '', ENT_QUOTES) ?>"
                                            data-x="<?= htmlspecialchars($r['x_url'] ?? '', ENT_QUOTES) ?>"
                                            data-fb="<?= htmlspecialchars($r['facebook_url'] ?? '', ENT_QUOTES) ?>"
                                        >Edit</button>

                                        <button
                                            class="btn btn-danger btn-sm delete-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-name="<?= htmlspecialchars($r['business_name'], ENT_QUOTES) ?>"
                                        >Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Footer Info</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <?php include __DIR__ . '/footer_settings_form_fields.php'; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content custom-modal">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Footer Info</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit-id">
                        <?php include __DIR__ . '/footer_settings_form_fields.php'; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Footer Info</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-id">
                        <p>Delete footer settings for <strong id="delete-name"></strong>?</p>
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
        function setLogoUi(modalEl, currentLogoPath) {
            const preview = modalEl.querySelector('.logo-preview');
            const current = modalEl.querySelector('.logo-current');
            const hiddenPath = modalEl.querySelector('input[name="logo_path"]');
            const fileInput = modalEl.querySelector('input[name="logo_file"]');

            if (hiddenPath) {
                hiddenPath.value = currentLogoPath || '';
            }

            if (preview) {
                if (currentLogoPath) {
                    preview.src = '../' + currentLogoPath.replace(/^\/+/, '');
                    preview.style.display = 'inline-block';
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                }
            }

            if (current) {
                if (currentLogoPath) {
                    current.textContent = 'Current: ' + currentLogoPath;
                    current.style.display = 'block';
                } else {
                    current.textContent = '';
                    current.style.display = 'none';
                }
            }

            if (fileInput) {
                fileInput.value = '';
                fileInput.onchange = function () {
                    const file = this.files && this.files[0];
                    if (!file || !preview) return;
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        preview.src = e.target.result;
                        preview.style.display = 'inline-block';
                    };
                    reader.readAsDataURL(file);
                };
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const addModal = document.getElementById('addModal');
            addModal.addEventListener('show.bs.modal', function () {
                setLogoUi(addModal, '');
            });

            const editModal = document.getElementById('editModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('edit-id').value = btn.getAttribute('data-id');
                setLogoUi(editModal, btn.getAttribute('data-logo') || '');
                editModal.querySelector('input[name="business_name"]').value = btn.getAttribute('data-business') || '';
                editModal.querySelector('input[name="copyright_text"]').value = btn.getAttribute('data-copyright') || '';
                editModal.querySelector('input[name="business_hours"]').value = btn.getAttribute('data-hours') || '';
                editModal.querySelector('textarea[name="address"]').value = btn.getAttribute('data-address') || '';
                editModal.querySelector('input[name="catering_phone"]').value = btn.getAttribute('data-catering') || '';
                editModal.querySelector('input[name="food_order_phone"]').value = btn.getAttribute('data-food') || '';
                editModal.querySelector('input[name="instagram_url"]').value = btn.getAttribute('data-ig') || '';
                editModal.querySelector('input[name="x_url"]').value = btn.getAttribute('data-x') || '';
                editModal.querySelector('input[name="facebook_url"]').value = btn.getAttribute('data-fb') || '';
            });

            const deleteModal = document.getElementById('deleteModal');
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('delete-id').value = btn.getAttribute('data-id');
                document.getElementById('delete-name').textContent = btn.getAttribute('data-name') || '';
            });
        });
    </script>
</body>
</html>
