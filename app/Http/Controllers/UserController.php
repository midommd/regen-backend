<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // 1. Get List of Makers (Publicly Accessible)
    public function getMakers()
    {
        // Fetch all users where role is 'maker'
        $makers = User::where('role', 'maker')->get();
        return response()->json($makers);
    }

    // 2. Get Single User Details (For Profile Modal)
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }
}