<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Rota de entrada
// Route::get('/', function(){
//     echo 'Olá Mundo';
//     DB::connection()->getPdo();
    
// });

// Route::view('/teste', 'teste')->middleware(('auth'));

Route::middleware('guest')->group(function(){
    // Registration Routes
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register_post', [AuthController::class, 'store_user'])->name('store_user');

    // Login Routes
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/submit_post', [AuthController::class, 'authenticate'])->name('authenticate');

    Route::get('/new_user_confirm/{token}', [AuthController::class, 'new_user_confirmation'])->name('new_user_confirmation');
    
});

Route::middleware('auth')->group(function(){
    Route::get('/',[AuthController::class, 'home'])->name('home');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
