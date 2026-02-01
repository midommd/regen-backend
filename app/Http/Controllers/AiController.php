<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function analyze(Request $request)
    {
        set_time_limit(120);

        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'No image received. Check PHP upload_max_filesize in XAMPP.'
            ], 400);
        }

        $file = $request->file('image');

        if (!$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed. Error Code: ' . $file->getError()
            ], 400);
        }

        try {
            $path = $file->getRealPath();
            
            $data = $this->gemini->analyzeImage($path);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error("Controller Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Backend Error: " . $e->getMessage()
            ], 500);
        }
    }
}