<?php
include("../connect.php");

$sql = "
SELECT 
    f.food_id,
    f.food_name,
    f.food_description,
    f.food_price,
    f.food_serving_size,
    f.food_image,
    c.food_category_id,
    c.food_category_name,
    c.food_category_description
FROM foods f
JOIN food_categories c ON f.food_category_id = c.food_category_id
WHERE f.food_is_available = 1
ORDER BY c.food_category_name, f.food_name
";

$result = $conn->query($sql);

$menu = [];

while ($row = $result->fetch_assoc()) {
    $catId = $row['food_category_id'];

    if (!isset($menu[$catId])) {
        $menu[$catId] = [
            "title" => $row['food_category_name'],
            "description" => $row['food_category_description'],
            "items" => []
        ];
    }

    $menu[$catId]['items'][] = [
        "id" => $row['food_id'],
        "name" => $row['food_name'],
        "description" => $row['food_description'],
        "price" => (float)$row['food_price'],
        "serving" => $row['food_serving_size'],
        "image" => $row['food_image']
    ];
}

header('Content-Type: application/json');
echo json_encode(array_values($menu));
