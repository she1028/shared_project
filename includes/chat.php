<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {

    require_once "../connect.php";

    $kbBusiness = "";
    $kbMenu = "";

    // --------------------
    // MENU DATA
    // --------------------
    $catRes = $conn->query("SELECT food_category_id, food_category_name FROM food_categories");
    while ($cat = $catRes->fetch_assoc()) {
        $kbMenu .= "Category: {$cat['food_category_name']}\n";

        $foodRes = $conn->query("
            SELECT food_name
            FROM foods
            WHERE food_category_id='{$cat['food_category_id']}'
            AND food_is_available=1
            LIMIT 10
        ");

        while ($food = $foodRes->fetch_assoc()) {
            $kbMenu .= "- {$food['food_name']}\n";
        }
        $kbMenu .= "\n";
    }

    // --------------------
    // BUSINESS DATA
    // --------------------
    $footerRes = $conn->query("SELECT * FROM footer_settings ORDER BY id DESC LIMIT 1");
    if ($footerRes && $footerRes->num_rows > 0) {
        $footer = $footerRes->fetch_assoc();

        $kbBusiness .= "Business name: {$footer['business_name']}\n";
        $kbBusiness .= "Business hours: {$footer['business_hours']}\n";
        $kbBusiness .= "Address: {$footer['address']}\n";
        $kbBusiness .= "Catering phone: {$footer['catering_phone']}\n";
        $kbBusiness .= "Food order phone: {$footer['food_order_phone']}\n";

        if (!empty($footer['instagram_url'])) $kbBusiness .= "Instagram: {$footer['instagram_url']}\n";
        if (!empty($footer['facebook_url'])) $kbBusiness .= "Facebook: {$footer['facebook_url']}\n";
        if (!empty($footer['x_url'])) $kbBusiness .= "X: {$footer['x_url']}\n";
    }

    // --------------------
    // STATIC KNOWLEDGE
    // --------------------
    $staticKB = "";
    if (file_exists("knowledge.json")) {
        $jsonData = json_decode(file_get_contents("knowledge.json"), true);
        if ($jsonData) {
            foreach ($jsonData as $section => $items) {
                $staticKB .= strtoupper($section) . ":\n";
                foreach ($items as $key => $value) {
                    $staticKB .= "- " . $value . "\n";
                }
                $staticKB .= "\n";
            }
        }
    }

    // --------------------
    // DECIDE KB TO USE
    // --------------------
    $question = strtolower($_POST['prompt']);
    if (preg_match("/business|name|contact|phone|address|hours/", $question)) {
        $kb = $kbBusiness . "\n" . $staticKB;
    } elseif (preg_match("/order|payment|cancel|policy|how|include|package|cater|event/", $question)) {
        $kb = $kbBusiness . "\n" . $staticKB;
    } else {
        $kb = $kbBusiness . "\nMENU:\n" . $kbMenu . "\n" . $staticKB;
    }

    // --------------------
    // BUILD PROMPT
    // --------------------
    $prompt = <<<EOT
You must answer ONLY using the information below.
If the information is insufficient, reply exactly:
"No data specified about the topic."

--- BEGIN DATA ---
$kb
--- END DATA ---

Question: {$_POST['prompt']}
Answer:
EOT;

    // --------------------
    // OLLAMA PAYLOAD
    // --------------------
    $payload = json_encode([
        "model" => "phi3",
        "prompt" => $prompt,
        "stream" => false,
        "options" => ["num_predict" => 200]
    ]);

    // --------------------
    // SEND TO OLLAMA
    // --------------------
    $ch = curl_init("http://127.0.0.1:11434/api/generate");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        echo "cURL error: " . curl_error($ch);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    $json = json_decode($response, true);
    if (isset($json['error'])) {
        echo "Ollama error: " . $json['error'];
        exit;
    }

    echo htmlspecialchars($json['response'] ?? "No response");
    exit;
}
?>
