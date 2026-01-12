<?php
include("../connect.php");

header('Content-Type: application/json');

// Fetch groups
$groups = [];
$result = $conn->query("SELECT id, group_key, group_name, description, cover_img FROM rental_groups ORDER BY group_name ASC");
while ($row = $result->fetch_assoc()) {
    $groups[(int)$row['id']] = [
        'group_id'    => (int)$row['id'],
        'group_key'   => $row['group_key'],
        'group_name'  => $row['group_name'],
        'description' => $row['description'],
        'cover_img'   => $row['cover_img'],
        'items'       => []
    ];
}

if (empty($groups)) {
    echo json_encode([]);
    exit;
}

// Fetch items (only active)
$itemStmt = $conn->prepare("SELECT id, group_id, item_category, name, price, img, stock, default_qty, is_active FROM rental_items WHERE is_active = 1 ORDER BY group_id, item_category, name");
$itemStmt->execute();
$itemsRes = $itemStmt->get_result();

$itemIds = [];
while ($row = $itemsRes->fetch_assoc()) {
    $normalized = [
        'id'          => $row['id'],
        'group_id'    => (int)$row['group_id'],
        'category'    => $row['item_category'],
        'item_category'=> $row['item_category'],
        'name'        => $row['name'],
        'price'       => (float)$row['price'],
        'image'       => $row['img'],
        'img'         => $row['img'],
        'stock'       => (int)$row['stock'],
        'default_qty' => (int)$row['default_qty'],
        'is_active'   => (int)$row['is_active'],
        'description' => '',
        'serving'     => ''
    ];

    $itemIds[] = $row['id'];
    if (isset($groups[(int)$row['group_id']])) {
        $groups[(int)$row['group_id']]['items'][$row['id']] = $normalized;
    }
}
$itemStmt->close();

// Fetch colors for the collected items
$colorsByItem = [];
if (!empty($itemIds)) {
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $types = str_repeat('s', count($itemIds));
    $colorStmt = $conn->prepare("SELECT id, item_id, color_name, color_stock FROM rental_item_colors WHERE item_id IN ($placeholders) ORDER BY id ASC");
    $colorStmt->bind_param($types, ...$itemIds);
    $colorStmt->execute();
    $colorsRes = $colorStmt->get_result();
    while ($row = $colorsRes->fetch_assoc()) {
        $colorsByItem[$row['item_id']][] = [
            'id'          => (int)$row['id'],
            'color_name'  => $row['color_name'],
            'color_stock' => isset($row['color_stock']) ? (int)$row['color_stock'] : 0
        ];
    }
    $colorStmt->close();
}

// Attach colors and normalize items array
foreach ($groups as &$group) {
    $groupItems = [];
    foreach ($group['items'] as $itemId => $item) {
        $item['colors'] = $colorsByItem[$itemId] ?? [];
        $groupItems[] = $item;
    }
    $group['items'] = $groupItems;
}
unset($group);

// Return array values to avoid sparse keys
echo json_encode(array_values($groups));
