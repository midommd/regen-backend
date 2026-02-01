<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    // Keeping your working model
    protected $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function analyzeImage($imagePath)
    {
        if (!file_exists($imagePath)) throw new \Exception("File missing");
        
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);

        // ⚡ THE "CREATIVE PHYSICS" PROMPT
        $prompt = "You are a Visionary Sustainable Designer. Analyze this waste item and invent a HIGH-VALUE Upcycled Product.
        
        CRITICAL RULES FOR CREATIVITY:
        1. ANALYZE MATERIAL PHYSICS:
           - If input is SOFT (Jeans, Fabric) -> Design a Bag, Wall Organizer, Pouf, or Suspended Hammock. (Do NOT design a table with fabric legs).
           - If input is HARD (Wood, Metal) -> Design Furniture, Shelving, or Lamps.
           - If input is HOLLOW (Bottles, Tires) -> Design Lighting, Planters, or Speakers.
        
        2. BE REASONABLE BUT BRILLIANT:
           - Don't make a fridge out of jeans.
           - DO make a 'Denim Chesterfield Ottoman' or a 'Modular Wall Pocket System'.
        
        GENERATE 3D ASSEMBLY (JSON):
        - 'type': 'box', 'rounded_box' (soft items), 'cylinder', 'cone', 'sphere' (lamps/decor), 'torus' (handles/rings), 'tube'.
        - 'modifiers': { 'taper': 0.5, 'radius_top': 5, 'radius_bottom': 2, 'hollow': true }.
        - 'material': 'wood_oak', 'wood_walnut', 'metal_brushed', 'metal_gold', 'denim_classic', 'denim_dark', 'leather_black', 'glass', 'concrete'.
        - 'dims': [width, height, depth] in CM.
        - 'pos': [x, y, z] relative to center (0,0,0).
        
        RETURN RAW JSON ONLY:
        {
            \"result_project\": \"(Creative Name)\",
            \"prototype_category\": \"(storage / lighting / seating / decor)\",
            \"marketing_pitch\": \"(Compelling 1-sentence pitch)\",
            \"difficulty\": \"Medium\",
            \"time\": \"4 Hours\",
            \"steps\": [\"Step 1...\", \"Step 2...\"],
            \"geometry\": {
                \"components\": [
                    { \"type\": \"rounded_box\", \"dims\": [40, 30, 40], \"pos\": [0, 15, 0], \"rot\": [0,0,0], \"color\": \"#283593\", \"material\": \"denim_dark\", \"name\": \"main_body\" },
                    { \"type\": \"torus\", \"dims\": [5, 1, 5], \"pos\": [0, 30, 0], \"rot\": [90,0,0], \"color\": \"#D4AF37\", \"material\": \"metal_gold\", \"name\": \"handle\" }
                ]
            }
        }";

        $data = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => [
                        'mime_type' => $mimeType,
                        'data' => $imageData
                    ]]
                ]
            ]]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        
        // Surgical Extraction to prevent JSON errors
        if (preg_match('/\{[\s\S]*\}/', $rawText, $matches)) {
            $cleanJson = $matches[0];
        } else {
            $cleanJson = str_replace(['```json', '```'], '', $rawText);
        }
        
        return json_decode($cleanJson, true);
    }
}