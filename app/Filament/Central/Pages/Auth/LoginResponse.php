<?php

namespace App\Filament\Central\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Explicitly redirect to the dashboard route we know exists
        return redirect()->to('/admin/dashboard');
    }
}