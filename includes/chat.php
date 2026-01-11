<?php
// --------------------------------------------------------
// PHP backend for handling AI requests (with Ollama)
// --------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {

    // 1. Load the knowledge base from data.txt
    $file_path = __DIR__ . DIRECTORY_SEPARATOR . 'data.txt';
    $kb = file_exists($file_path) ? file_get_contents($file_path) : ''; // Read the contents of data.txt

    // 2. Escape special characters in user input to avoid breaking heredoc
    $user_prompt = str_replace(['$', '{', '}'], ['\$','\{','\}'], $_POST['prompt']); // Escape special characters

    // 3. Construct the strict prompt with role instructions and data
    $prompt = <<<EOT
You are an assistant that must answer questions using *only* the information
provided below.
Do not use outside knowledge or assumptions.
If the information is insufficient to answer, respond exactly with:
"No data specified about the topic."

--- BEGIN DATA ---
$kb
--- END DATA ---

Question: $user_prompt
Answer:
EOT;

    // 4. Prepare the payload to send to Ollama API
    $payload = json_encode([
        'model' => 'llama3',  // The model you are using
        'prompt' => $prompt,
        'stream' => false
    ]);

    // 5. Send the request to the Ollama API (or any other model)
    $ch = curl_init('http://127.0.0.1:11434/api/generate');  // Ollama API endpoint (adjust if needed)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);

    // Check for errors in the API request
    if ($response === false) {
        echo "Curl error: " . curl_error($ch);
        exit;
    }

    // Close the cURL session
    curl_close($ch);

    // 6. Decode the response from the AI model
    $data = json_decode($response, true);
    $reply = $data['response'] ?? $data['output'] ?? '(no reply)'; // Handle missing or unexpected responses

    // 7. Return the AI's response to the front-end (chatbot)
    echo htmlspecialchars($reply);  // Sanitize and output plain text
    exit;
}
?>
