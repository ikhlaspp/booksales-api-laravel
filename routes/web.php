<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\GenreController;
use App\Http\Controllers\AuthorController;

Route::get('/genres', [GenreController::class, 'index']);
Route::get('/authors', [AuthorController::class, 'index']);

