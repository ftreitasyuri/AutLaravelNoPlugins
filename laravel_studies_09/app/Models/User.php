<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //
    use HasFactory;

    // Atributes that are hidden for serialization
    protected $hidden = [
        'password',
        'token'
    ];
}
