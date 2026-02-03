<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * 
     * Redirect based on role:
     * - admin -> /admin (User Management)
     * - admin_subdit -> /min-ops (Case Management only)
     * - petugas -> /dashboard (with Pawas selection prompt)
     * - pimpinan -> /pimpinan/dashboard (Executive Dashboard)
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        
        // Admin -> User Management
        if ($user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }
        
        // Pimpinan -> Executive Dashboard (exclusive access)
        if ($user->role === 'pimpinan') {
            return redirect()->intended(route('pimpinan.dashboard', absolute: false));
        }
        
        // Admin Subdit -> Subdit Dashboard (Unit Management)
        if ($user->role === 'admin_subdit') {
            return redirect()->intended(route('subdit.dashboard', absolute: false));
        }
        
        // Petugas -> Case Entry (with Pawas selection)
        if ($user->role === 'petugas') {
            return redirect()->intended(route('dashboard', absolute: false));
        }
        
        // Fallback
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
