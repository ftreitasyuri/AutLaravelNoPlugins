<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as AuthUser;

class User extends AuthUser
{
    //
    use HasFactory;
    // Para não deletar e só deixar inativo
    use SoftDeletes;

    // Atributes that are hidden for serialization
    protected $hidden = [
        'password',
        'token'
    ];
}
