<?php
include("../connect.php");

header('Content-Type: application/json');

$validCategories = ['wedding', 'children', 'debut', 'corporate'];

$category = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : '';
$slug     = isset($_GET['slug']) ? strtolower(trim($_GET['slug'])) : '';

if ($category !== '' && !in_array($category, $validCategories, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category.']);
    exit;
}

try {
    // Determine ordering column (item_order if present, otherwise sort_order)
    $orderColumn = 'sort_order';
    $colRes = $conn->query("SHOW COLUMNS FROM package_inclusion_items LIKE 'item_order'");
    if ($colRes && $colRes->num_rows > 0) {
        $orderColumn = 'item_order';
    }
    if ($colRes) {
        $colRes->close();
    }

    // Build base detail query
    $detailSql = "SELECT id, category, slug, offer, title, price_label, note, image_path FROM package_details";
    $conds = [];
    $types = '';
    $params = [];

    if ($category !== '') {
        $conds[] = "category = ?";
        $types .= 's';
        $params[] = $category;
    }
    if ($slug !== '') {
        $conds[] = "slug = ?";
        $types .= 's';
        $params[] = $slug;
    }

    if (!empty($conds)) {
        $detailSql .= ' WHERE ' . implode(' AND ', $conds);
    }
    $detailSql .= ' ORDER BY category, id';

    $detailStmt = $conn->prepare($detailSql);
    if ($types !== '') {
        $detailStmt->bind_param($types, ...$params);
    }
    $detailStmt->execute();
    $detailRes = $detailStmt->get_result();

    $details = [];
    $detailIds = [];
    while ($row = $detailRes->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $details[$row['id']] = $row;
        $detailIds[] = $row['id'];
    }
    $detailStmt->close();

    if (empty($details)) {
        echo json_encode([]);
        exit;
    }

    // Fetch inclusion items for collected detail IDs
    $placeholders = implode(',', array_fill(0, count($detailIds), '?'));
    $typesItems = str_repeat('i', count($detailIds));

    $itemSql = "SELECT package_detail_id, inclusion_type, item_text, item_order FROM package_inclusion_items WHERE package_detail_id IN ($placeholders) ORDER BY package_detail_id, $orderColumn, id";
    $itemStmt = $conn->prepare($itemSql);
    $itemStmt->bind_param($typesItems, ...$detailIds);
    $itemStmt->execute();
    $itemRes = $itemStmt->get_result();

    $byDetail = [];
    while ($row = $itemRes->fetch_assoc()) {
        $detailId = (int)$row['package_detail_id'];
        $type = $row['inclusion_type'];
        $text = $row['item_text'];
        $qty  = isset($row['item_order']) ? intval($row['item_order']) : 0;
        if (!isset($byDetail[$detailId])) {
            $byDetail[$detailId] = [
                'menu' => [],
                'rentals' => [],
                'decorations' => [],
                'services' => []
            ];
        }
        if (isset($byDetail[$detailId][$type])) {
            $byDetail[$detailId][$type][] = [
                'text' => $text,
                'quantity' => $qty
            ];
        }
    }
    $itemStmt->close();

    // Build response
    $response = [];
    foreach ($details as $detailId => $detail) {
        $grouped = $byDetail[$detailId] ?? [
            'menu' => [],
            'rentals' => [],
            'decorations' => [],
            'services' => []
        ];

        $response[] = [
            'category' => $detail['category'],
            'slug' => $detail['slug'],
            'id' => $detail['slug'],
            'offer' => $detail['offer'],
            'title' => $detail['title'],
            'price' => $detail['price_label'],
            'price_label' => $detail['price_label'],
            'note' => $detail['note'],
            'image' => $detail['image_path'],
            'menu' => $grouped['menu'],
            'rentals' => $grouped['rentals'],
            'decorations' => $grouped['decorations'],
            'services' => $grouped['services']
        ];
    }

    echo json_encode($response);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
