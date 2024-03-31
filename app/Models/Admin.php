<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;


class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory;
    protected $table = 'admins';
    protected $fillable = [
        'username',
        'fullname',
        'address',
        'gender',
        'password',
        'email',
        'phone_number',
        'status',
        'office',
    ];
}
