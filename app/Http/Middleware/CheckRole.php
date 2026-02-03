<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 * 
 * Memeriksa apakah user memiliki role yang diizinkan
 * Usage: ->middleware('role:admin') atau ->middleware('role:admin,petugas')
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Allowed roles (comma-separated)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user's role is in allowed roles
        if (!in_array($user->role, $roles)) {
            // Redirect based on role to their appropriate home page
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'Anda tidak memiliki akses ke halaman tersebut');
            }
            
            if ($user->role === 'admin_subdit') {
                return redirect()->route('subdit.dashboard')
                    ->with('error', 'Anda tidak memiliki akses ke halaman tersebut');
            }
            
            if ($user->role === 'pimpinan') {
                return redirect()->route('pimpinan.dashboard')
                    ->with('error', 'Anda tidak memiliki akses ke halaman tersebut');
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut');
        }

        return $next($request);
    }
}
