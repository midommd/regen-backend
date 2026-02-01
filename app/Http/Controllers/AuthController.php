<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MakerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
class AuthController extends Controller
{

    private function formatUserResponse($user)
    {
        $user->load('makerProfile');
        
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
        ];

        if ($user->role === 'maker' && $user->makerProfile) {
            $data['field'] = $user->makerProfile->field;
            $data['experience'] = $user->makerProfile->experience;
            $data['bio'] = $user->makerProfile->bio;
            $data['rating'] = $user->makerProfile->rating;
        }

        return $data;
    }

  public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string',
            'role' => 'required|in:user,maker',
            'field' => 'nullable|required_if:role,maker|string',
            'experience' => 'nullable|required_if:role,maker|integer',
        ]);

        $defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($fields['name']) . '&background=0D9488&color=fff&size=128&bold=true';

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
            'role' => $fields['role'],
            'avatar' => $defaultAvatar, 
            'bio' => 'Ready to turn waste into wonder! 🌿' 
        ]);

        if ($fields['role'] === 'maker') {
            MakerProfile::create([
                'user_id' => $user->id,
                'field' => $fields['field'],
                'experience' => $fields['experience'],
                'bio' => $fields['bio'] ?? 'Professional Maker ready to help.',
                'rating' => 5.0
            ]);
        }

        $token = $user->createToken('regen_token')->plainTextToken;

        return response()->json([
            'user' => $this->formatUserResponse($user), 
            'token' => $token,
            'message' => 'Registration successful'
        ], 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // 1. Check if user exists
        $user = User::where('email', $fields['email'])->first();

        // 2. Check Password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 3. Create Token
        $token = $user->createToken('regen_token')->plainTextToken;

        // 4. Return formatted user
        return response()->json([
            'user' => $this->formatUserResponse($user),
            'token' => $token
        ], 200);
    }
    
    // --- GET MAKERS LIST (For the Hire Page) ---
    public function getMakers()
    {
        $makers = User::where('role', 'maker')
            ->with('makerProfile')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'field' => $user->makerProfile->field ?? 'General',
                    'rating' => $user->makerProfile->rating ?? 5.0,
                    'experience' => $user->makerProfile->experience ?? 0,
                    'bio' => $user->makerProfile->bio ?? '',
                ];
            });

        return response()->json($makers);
    }

    // --- LOGOUT ---
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
    // --- UPDATE PROFILE ---
    // app/Http/Controllers/AuthController.php

public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            // 1. Update User Table
            $user->name = $request->input('name');
            $user->bio = $request->input('bio');
            
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = '/storage/' . $path;
            }
            $user->save();

            // 2. Update Maker Profile (Direct Database Query)
            if ($user->role === 'maker') {
                DB::table('maker_profiles')->updateOrInsert(
                    ['user_id' => $user->id], // Find by User ID
                    [
                        'field' => $request->input('field'),
                        'experience' => $request->input('experience'),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user // We return user, frontend will re-fetch maker data if needed
            ]);

        } catch (\Exception $e) {
            // ✅ Log the real error so we can see it
            return response()->json([
                'success' => false,
                'message' => 'DB Error: ' . $e->getMessage() 
            ], 500);
        }
    }
}