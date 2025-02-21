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
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/submit', [AuthController::class, 'authenticate'])->name('authenticate');
});

Route::middleware('auth')->group(function(){
    Route::get('/',[AuthController::class, 'home'])->name('home');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
