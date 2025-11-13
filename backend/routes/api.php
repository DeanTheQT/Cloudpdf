<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ThesisController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes handle authentication, PDF uploads, and optional debug routes.
| You can remove auth middleware on /upload if you don't need security.
|
*/

// ------------------------
// Authentication routes
// ------------------------
// Public routes
Route::middleware(['web'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
// ------------------------
// PDF Upload route
// ------------------------
// Option 1: authenticated (secure)
// Route::middleware('auth:sanctum')->post('/upload', [ThesisController::class, 'upload']);

// Option 2: public (no login required)
Route::post('/upload', [ThesisController::class, 'upload']);
Route::get('/theses', [ThesisController::class, 'index']);
Route::get('/theses/download/{id}', [ThesisController::class, 'download']);

// ------------------------
// Debug route for env variables
// ------------------------
Route::get('/debug-openai-key', function () {
    return response()->json([
        'OPENAI_API_KEY' => env('OPENAI_API_KEY', 'NOT SET')
    ]);
});
