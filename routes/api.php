<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\AdminConversationController;
use App\Http\Middleware\Admin;
use App\Http\Middleware\Customer;
use App\Models\User;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\PublicCatalogController;

Route::get('/catalog', [PublicCatalogController::class, 'index']);
Route::apiResource('genres', GenreController::class)->only(['index', 'show']);
Route::apiResource('authors', AuthorController::class)->only(['index', 'show']);
Route::apiResource('books', BookController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum', Admin::class])->group(function () {
    Route::apiResource('genres', GenreController::class)->except(['index', 'show']);
    Route::apiResource('authors', AuthorController::class)->except(['index', 'show']);
    Route::apiResource('books', BookController::class)->except(['index', 'show']);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'destroy']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Users management (read-only for admin)
    Route::get('/users', [UserController::class, 'index']);

    // Admin conversation / inbox
    Route::get('/admin/conversations', [AdminConversationController::class, 'index']);
    Route::get('/admin/conversations/{id}', [AdminConversationController::class, 'show']);
    Route::post('/admin/conversations/{id}/messages', [AdminConversationController::class, 'reply']);
    Route::put('/admin/conversations/{id}/read', [AdminConversationController::class, 'markRead']);
});

Route::middleware(['auth:sanctum', Customer::class])->group(function () {
    Route::apiResource('transactions', TransactionController::class)->only(['store']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/transactions', [TransactionController::class, 'userTransactions']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::apiResource('transactions', TransactionController::class)->only(['update', 'show']);
    Route::get('/conversations', [ConversationController::class, 'show']);
    Route::post('/conversations/messages', [ConversationController::class, 'sendMessage']);
    Route::get('/conversations/messages', [ConversationController::class, 'poll']);
    Route::put('/conversations/read', [ConversationController::class, 'markRead']);
});

// Midtrans Notification Callback (no auth needed - server-to-server)
Route::post('/midtrans/callback', [TransactionController::class, 'midtransCallback']);

// General contact form (public, no auth)
Route::post('/contact', [ContactController::class, 'store']);
