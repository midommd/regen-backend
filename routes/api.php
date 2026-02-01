<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No Login Required)
|--------------------------------------------------------------------------
*/
// ✅ CORRECT: This is the one we want to use
Route::get('/makers', [UserController::class, 'getMakers']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::get('/users/{id}/projects', [ProjectController::class, 'getUserProjects']);
Route::get('/marketplace', [ProjectController::class, 'getMarketplace']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/analyze-image', [AiController::class, 'analyze']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Login Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user()->load('makerProfile');
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ❌ REMOVED DUPLICATE: Route::get('/makers', ...) was causing the 401 error
    
    Route::post('/projects', [ProjectController::class, 'store']); // Upload
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::post('/projects/{id}/sell', [ProjectController::class, 'listForSale']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    
    // Chat Routes
    Route::post('/chat/start', [ChatController::class, 'startChat']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/chat/conversations', [ChatController::class, 'getConversations']); // Unified route name
    Route::get('/chat/{id}/messages', [ChatController::class, 'getMessages']);
    Route::put('/chat/message/{id}', [ChatController::class, 'editMessage']);
    Route::delete('/chat/message/{id}', [ChatController::class, 'deleteMessage']);
});