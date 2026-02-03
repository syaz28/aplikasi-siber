<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsurePawasSelected Middleware
 * 
 * Forces petugas role users to select their active identity (Pawas)
 * before accessing case management features.
 */
class EnsurePawasSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only apply to petugas role
        if ($user && $user->role === 'petugas') {
            // Check if Pawas is selected in session
            if (!session()->has('active_pawas_id')) {
                // Redirect to Pawas selection page
                return redirect()->route('pawas.select');
            }
        }

        return $next($request);
    }
}
