<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Tenant extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'address',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


}
