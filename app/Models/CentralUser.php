<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class CentralUser extends Authenticatable
{
    use HasRoles;

    protected $connection = 'central';
    protected $table = 'users';
    // ...
}