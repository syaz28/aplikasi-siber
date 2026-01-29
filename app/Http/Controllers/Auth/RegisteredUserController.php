<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RegisteredUserController - Unified Officer Registration
 * 
 * Creates User records directly with all profile fields.
 * Uses NRP-based authentication.
 */
class RegisteredUserController extends Controller
{
    /**
     * Pangkat options for registration dropdown
     */
    private const PANGKAT_OPTIONS = [
        'AKBP', 'Kompol', 'AKP', 'Iptu', 'Ipda', 'Aiptu', 'Aipda',
        'Bripka', 'Brigadir', 'Briptu', 'Bripda', 'PNSD'
    ];

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'pangkatOptions' => collect(self::PANGKAT_OPTIONS)->map(fn($p) => [
                'value' => $p,
                'label' => $p,
            ]),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * Creates User directly with all profile fields.
     * Auto-redirects to login page.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'nrp' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                'unique:users,nrp',
            ],
            'pangkat' => ['required', 'string', 'max:20'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nrp.required' => 'NRP wajib diisi.',
            'nrp.regex' => 'NRP hanya boleh berisi angka.',
            'nrp.unique' => 'NRP sudah terdaftar dalam sistem.',
            'pangkat.required' => 'Pangkat wajib dipilih.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        try {
            $user = User::create([
                'name' => $validated['nama'],
                'nrp' => $validated['nrp'],
                'pangkat' => $validated['pangkat'],
                'jabatan' => $validated['jabatan'] ?? null,
                'telepon' => $validated['telepon'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'petugas', // Default role for new registrations
                'is_active' => true,
            ]);

            event(new Registered($user));

            // Redirect to login page with success message (do NOT auto-login)
            return redirect()->route('login')->with('status', 'Registrasi berhasil! Silakan login dengan NRP Anda.');

        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('Registration failed: ' . $e->getMessage());
            
            return back()->withErrors([
                'nrp' => 'Terjadi kesalahan saat mendaftarkan akun. Silakan coba lagi.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }
    }
}
