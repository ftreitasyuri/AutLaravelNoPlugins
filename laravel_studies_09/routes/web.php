<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use Faker\Guesser\Name;
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

    // Recuperar senha
    Route::get('/forgot_password', [AuthController::class, 'forgot_password'])->name('forgot_password');
    Route::post('/forgot_password', [AuthController::class, 'send_reset_password_link'])->name('send_reset_password_link');


    // Resete password
    Route::get('/reset_password/{token}', [AuthController::class, 'reset_password'])->name('reset_password');
    Route::post('/reset_password', [AuthController::class, 'reset_password_update'])->name('reset_password_update');

    
});

Route::middleware('auth')->group(function(){
    Route::get('/',[MainController::class, 'home'])->name('home');
    
    // Profile - Change Password
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [AuthController::class, 'change_password'])->name('change_password');

    
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    // Delete account
    Route::post('/delete_account', [AuthController::class, 'delete_account'])->name('delete_account');
});
