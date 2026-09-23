<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class TenantUser extends Authenticatable
{
    use HasRoles;

    protected $table = 'users';
    // Uses the active tenant connection automatically (Stancl swaps it)

    protected $guard_name = 'web';
	protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}