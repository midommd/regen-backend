<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        // 1. SMART VALIDATION (Modified to allow Links OR Images)
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'media_type' => 'nullable|in:image,link', // 'image' or 'link'
            
            // ✅ FIX: Image is only required if external_link is missing
            'image' => 'nullable|image|max:10240|required_without:external_link',
            
            // ✅ FIX: Link is required if image is missing
            'external_link' => 'nullable|url|required_without:image',
        ]);

        $path = null;
        $aiData = null;
        $status = 'completed'; // Default status for manual portfolio uploads
        $materialDetected = 'Manual Upload'; // Default material for portfolio

        // 2. Save the File to Storage (Only if image exists)
        if ($request->hasFile('image')) {
            // Stores in "storage/app/public/projects"
            $path = $request->file('image')->store('projects', 'public');
            
            // 3. Generate "AI" Data (EXISTING LOGIC PRESERVED)
            // We only simulate AI if it's a raw scan (no title provided)
            if (!$request->filled('title')) {
                $status = 'analyzing';
                $materialDetected = 'Wood';
                
                // Your existing AI Simulation Data
                $aiData = [
                    'material' => 'Wood / Pallet',
                    'confidence' => 0.95,
                    'suggestions' => [
                        [
                            'title' => 'Rustic Garden Chair',
                            'difficulty' => 'Medium',
                            'time' => '4 hours',
                            'tools' => ['Hammer', 'Saw', 'Nails']
                        ],
                        [
                            'title' => 'Wall-Mounted Shelf',
                            'difficulty' => 'Easy',
                            'time' => '2 hours',
                            'tools' => ['Drill', 'Screws']
                        ]
                    ]
                ];
            }
        }

        // 4. Create Database Record
        // We use $validated data where available, otherwise defaults
        $project = Project::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?? 'New Scan',
            'description' => $validated['description'] ?? 'Analyzed Waste Item',
            'category' => $validated['category'] ?? 'Unsorted',
            
            // ✅ FIX: Logic to handle null image path
            'image_path' => $path, 
            'external_link' => $validated['external_link'] ?? null,
            'media_type' => $validated['media_type'] ?? 'image',
            
            'status' => $status,
            'material_detected' => $materialDetected,
            
            // ✅ FIX: Handle null AI data
            'ai_suggestions' => $aiData ? json_encode($aiData) : null,
            'is_for_sale' => false // Default to not for sale
        ]);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project,
            // Return full URL so frontend can display it (handle null path)
            'image_url' => $path ? asset('storage/' . $path) : null 
        ], 201);
    }

    public function index(Request $request)
    {
        // Get all projects for the logged-in user
        return $request->user()->projects()->latest()->get()->map(function($project) {
            // Append a new field 'image_url' to the JSON response
            $project->image_url = $project->image_path ? asset('storage/' . $project->image_path) : null;
            return $project;
        });
    }

    // 1. Get Marketplace Items (Public)
    public function getMarketplace()
    {
        return Project::where('is_for_sale', true)
            ->with('user:id,name,avatar') // Get seller info
            ->latest()
            ->get()
            ->map(function($p) {
                $p->image_url = $p->image_path ? asset('storage/' . $p->image_path) : null;
                return $p;
            });
    }

    // 2. List an Item for Sale
    public function listForSale(Request $request, $id)
    {
        $project = Project::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        
        $request->validate([
            'price' => 'required|numeric|min:0',
            'description' => 'required|string|max:500'
        ]);

        $project->update([
            'is_for_sale' => true,
            'price' => $request->price,
            'description' => $request->description,
            'status' => 'completed' // Ensure it's marked complete
        ]);

        return response()->json(['message' => 'Listed on Marketplace!', 'project' => $project]);
    }

    public function destroy($id)
    {
        // 1. Get the current user's ID safely
        $userId = Auth::id(); 

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Find the project that belongs to THIS user
        $project = Project::where('id', $id)
                          ->where('user_id', $userId)
                          ->first();

        // 3. Check if it exists
        if (!$project) {
            return response()->json(['message' => 'Project not found or you do not own it.'], 404);
        }

        // 4. Delete it
        $project->delete();

        return response()->json(['success' => true, 'message' => 'Item deleted successfully']);
    }

    public function update(Request $request, $id)
    {
        // 1. Get User ID Safely
        $userId = \Illuminate\Support\Facades\Auth::id(); 

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Find the project (Ensure it belongs to THIS user)
        $project = Project::where('id', $id)
                          ->where('user_id', $userId)
                          ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found or unauthorized'], 404);
        }

        // 3. Validate Data
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        // 4. Update & Save
        $project->title = $request->input('title');
        $project->description = $request->input('description');
        $project->category = $request->input('category');
        $project->price = $request->input('price');
        $project->is_for_sale = true; // Mark as For Sale
        $project->save();

        return response()->json([
            'success' => true, 
            'message' => 'Item listed successfully!',
            'project' => $project
        ]);
    }
    // Get public projects for a specific user (Maker Portfolio)
    public function getUserProjects($id)
    {
        return Project::where('user_id', $id)
            ->latest()
            ->get()
            ->map(function($p) {
                // Return null if no image, so frontend handles the placeholder
                $p->image_url = $p->image_path ? asset('storage/' . $p->image_path) : null;
                return $p;
            });
    }
}