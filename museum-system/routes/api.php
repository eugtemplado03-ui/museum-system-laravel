<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArtifactLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarouselController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExhibitController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\MuseumInfoController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\TtsController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json(['ok' => true, 'database' => 'connected']);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json(['ok' => false, 'database' => 'disconnected'], 503);
    }
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('jwt.auth:admin');
    Route::get('/me', [AuthController::class, 'me'])->middleware('jwt.auth');
});

// Exhibits routes
Route::prefix('exhibits')->group(function () {
    Route::get('/', [ExhibitController::class, 'index']);
    Route::get('/recommended', [ExhibitController::class, 'recommended']);
    Route::get('/categories', [ExhibitController::class, 'categories']);
    Route::get('/{code}', [ExhibitController::class, 'show']);
    Route::get('/{code}/qr', [ExhibitController::class, 'qr']);
    Route::get('/{code}/ratings', [ExhibitController::class, 'ratings']);
    Route::post('/{code}/ratings', [ExhibitController::class, 'submitRating']);
    Route::post('/{code}/scan', [ExhibitController::class, 'scan']);

    // Admin exhibit operations
    Route::middleware('jwt.auth:admin')->group(function () {
        Route::post('/', [ExhibitController::class, 'store']);
        Route::put('/{id}', [ExhibitController::class, 'update']);
        Route::delete('/{id}', [ExhibitController::class, 'destroy']);
        Route::post('/categories', [ExhibitController::class, 'addCategory']);
        Route::delete('/categories/{name}', [ExhibitController::class, 'deleteCategory']);
    });
});

// Visitors routes
Route::prefix('visitors')->group(function () {
    Route::post('/checkin', [VisitorController::class, 'checkin']);
    Route::get('/verify/{code}', [VisitorController::class, 'verifyPass']);
    Route::post('/verify', [VisitorController::class, 'verifyPass']);
    Route::post('/verify-pass', [VisitorController::class, 'verifyPass']);
    Route::post('/checkout', [VisitorController::class, 'checkout']);

    // Admin visitor operations
    Route::middleware('jwt.auth:admin')->group(function () {
        Route::get('/', [VisitorController::class, 'index']);
        Route::get('/stats', [VisitorController::class, 'stats']);
        Route::get('/{id}', [VisitorController::class, 'show']);
    });
});

// Chatbot route
Route::post('/chat', [ChatController::class, 'chat']);

// Text-to-Speech route
Route::post('/tts', [TtsController::class, 'generate']);
Route::post('/tts/speak', [TtsController::class, 'speak']);
Route::post('/tts/translate', [TtsController::class, 'translate']);

// Favorites routes
Route::prefix('favorites')->group(function () {
    Route::get('/{visitorId}', [FavoriteController::class, 'forVisitor']);
    Route::post('/', [FavoriteController::class, 'store']);
    Route::delete('/', [FavoriteController::class, 'destroy']);
});

// Artifact logs routes
Route::prefix('artifact-logs')->group(function () {
    Route::post('/', [ArtifactLogController::class, 'store']);
    Route::get('/', [ArtifactLogController::class, 'index'])->middleware('jwt.auth:admin');
});

// Programs routes
Route::prefix('programs')->group(function () {
    Route::get('/', [ProgramController::class, 'index']);
    Route::get('/{id}', [ProgramController::class, 'show']);
    Route::middleware('jwt.auth:admin')->group(function () {
        Route::post('/', [ProgramController::class, 'store']);
        Route::put('/{id}', [ProgramController::class, 'update']);
        Route::delete('/{id}', [ProgramController::class, 'destroy']);
    });
});

// Events routes
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/{id}', [EventController::class, 'show']);
    Route::middleware('jwt.auth:admin')->group(function () {
        Route::post('/', [EventController::class, 'store']);
        Route::put('/{id}', [EventController::class, 'update']);
        Route::delete('/{id}', [EventController::class, 'destroy']);
    });
});

// Gallery routes
Route::prefix('gallery')->group(function () {
    Route::get('/', [GalleryController::class, 'index']);
    Route::get('/{id}', [GalleryController::class, 'show']);
    Route::middleware('jwt.auth:admin')->group(function () {
        Route::post('/', [GalleryController::class, 'store']);
        Route::put('/{id}', [GalleryController::class, 'update']);
        Route::delete('/{id}', [GalleryController::class, 'destroy']);
    });
});

// Museum Info routes
Route::prefix('museum-info')->group(function () {
    Route::get('/', [MuseumInfoController::class, 'show']);
    Route::put('/', [MuseumInfoController::class, 'update'])->middleware('jwt.auth:admin');
});

// Carousel routes
Route::prefix('carousel')->group(function () {
    Route::get('/', [CarouselController::class, 'index']);
    Route::middleware('jwt.auth:admin')->group(function () {
        Route::post('/', [CarouselController::class, 'store']);
        Route::put('/{id}', [CarouselController::class, 'update']);
        Route::delete('/{id}', [CarouselController::class, 'destroy']);
    });
});

// Admin routes
Route::prefix('admin')->middleware('jwt.auth:admin')->group(function () {
    Route::get('/me', [AdminController::class, 'me']);
    Route::get('/analytics', [AdminController::class, 'analytics']);
    Route::get('/analytics/daily', [AdminController::class, 'dailyAnalytics']);
    Route::get('/ratings', [AdminController::class, 'ratings']);
    Route::delete('/ratings/{id}', [AdminController::class, 'deleteRating']);
});
