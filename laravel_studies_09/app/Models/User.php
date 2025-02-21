<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthUser;

class User extends AuthUser
{
    //
    use HasFactory;

    // Atributes that are hidden for serialization
    protected $hidden = [
        'password',
        'token'
    ];
}
