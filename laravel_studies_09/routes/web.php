<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Rota de entrada
Route::get('/', function(){
    // echo 'Olá Mundo';
    DB::connection()->getPdo();
    
});
