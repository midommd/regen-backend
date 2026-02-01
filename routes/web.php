<?php
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-blender', function () {

    // Adjust this path to where your blender.exe really is
    $blenderPath = '"C:\\Program Files\\Blender Foundation\\Blender 5.0\\blender.exe"';

    $output = shell_exec($blenderPath . ' --version 2>&1');
    echo nl2br($output);

});
Route::get('/check-models', function () {
    $apiKey = env('GEMINI_API_KEY');
    
    if (!$apiKey) {
        return response()->json(['error' => 'No API Key found in .env'], 500);
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Bypass SSL for local testing stability
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return response()->json(['curl_error' => $curlError], 500);
    }

    $data = json_decode($response, true);

    return response()->json([
        'status_code' => $httpCode,
        'available_models' => $data['models'] ?? $data
    ]);
});


Route::get('/test-gemini-final', function () {
    // 1. SETUP
    $apiKey = env('GEMINI_API_KEY');
    
    // ✅ USING YOUR VERIFIED MODEL (Gemini 2.0 Flash)
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

    // 2. MOCK DATA (Simulate a table leg analysis)
    // We send a text-only prompt first to verify the logic without needing a real image upload
    $payload = [
        'contents' => [[
            'parts' => [[
                'text' => "You are an Industrial Designer. Generate a JSON 3D blueprint for a 'Modern Concrete Side Table'. 
                RETURN JSON ONLY.
                Format: { \"geometry\": { \"components\": [ { \"type\": \"cylinder\", \"material\": \"concrete\" } ] } }"
            ]]
        ]],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
        ]
    ];

    // 3. EXECUTE CURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // 4. ANALYZE RESULTS
    echo "<h1>Gemini API Diagnostic Test</h1>";
    echo "<strong>Model:</strong> gemini-2.0-flash<br>";
    echo "<strong>HTTP Status:</strong> " . $httpCode . " (200 is Success)<br>";
    
    if ($error) {
        echo "<h3 style='color:red'>CURL Error: $error</h3>";
        return;
    }

    if ($httpCode !== 200) {
        echo "<h3 style='color:red'>API Failure</h3>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        return;
    }

    // 5. TEST THE JSON PARSER (The logic I gave you)
    $json = json_decode($response, true);
    $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'NO TEXT FOUND';
    
    echo "<h3>Raw AI Response:</h3>";
    echo "<div style='background:#f0f0f0; padding:10px; border:1px solid #ccc'><pre>" . htmlspecialchars($rawText) . "</pre></div>";

    // Regex Extraction Test
    if (preg_match('/\{[\s\S]*\}/', $rawText, $matches)) {
        $cleanJson = $matches[0];
        $decoded = json_decode($cleanJson, true);
        
        if ($decoded) {
            echo "<h3 style='color:green'>✅ JSON Parsing: SUCCESS</h3>";
            echo "The parser successfully isolated the JSON object from the text.";
            echo "<pre>" . print_r($decoded, true) . "</pre>";
        } else {
            echo "<h3 style='color:orange'>⚠️ JSON Parsing: DECODE FAIL</h3>";
            echo "Extracted string was not valid JSON. Error: " . json_last_error_msg();
        }
    } else {
        echo "<h3 style='color:red'>❌ JSON Parsing: REGEX FAIL</h3>";
        echo "Could not find a JSON object in the response.";
    }
});
Route::get('/test-gemini', function () {
    $apiKey = env('GEMINI_API_KEY');
    
    // ✅ Using the model that exists in your list
    $model = 'gemini-flash-latest';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    echo "<h1>🧪 Testing Model: <span style='color:blue'>{$model}</span></h1>";

    // 1. Prepare Data
    $data = [
        'contents' => [[
            'parts' => [['text' => 'Reply with exactly one word: Working.']]
        ]]
    ];

    // 2. RAW CURL SETUP (The "Nuclear" Option)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // 🛑 SSL Bypass
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // 3. Execute
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 4. Analyze Result
    if ($curlError) {
        echo "<h2 style='color:red'>❌ CONNECTION FAILED</h2>";
        echo "<strong>CURL Error:</strong> " . $curlError;
        return;
    }

    echo "<h3>HTTP Status: {$httpCode}</h3>";

    if ($httpCode >= 400) {
        echo "<h2 style='color:red'>❌ GOOGLE REFUSED</h2>";
        echo "<strong>Response:</strong> " . $response;
    } else {
        $json = json_decode($response, true);
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'Empty Response';
        
        echo "<h2 style='color:green'>✅ SUCCESS!</h2>";
        echo "<strong>AI Replied:</strong> " . $text;
    }
});