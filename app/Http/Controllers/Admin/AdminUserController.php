<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pangkat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AdminUserController
 * 
 * CRUD operations for User management (Admin only)
 */
class AdminUserController extends Controller
{
    /**
     * Display a listing of users with pagination.
     */
    public function index(Request $request): InertiaResponse
    {
        $query = User::query()
            ->orderBy('created_at', 'desc');

        // Search by name, email, or nrp
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nrp', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Filter by subdit
        if ($request->filled('subdit')) {
            $query->where('subdit', $request->input('subdit'));
        }

        $users = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'subdit']),
            'roleOptions' => self::getRoleOptions(),
            'subditOptions' => self::getSubditOptions(),
            'unitOptions' => self::getUnitOptions(),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Users/Create', [
            'roleOptions' => self::getRoleOptions(),
            'subditOptions' => self::getSubditOptions(),
            'unitOptions' => self::getUnitOptions(),
            'pangkatOptions' => Pangkat::getDropdownOptions(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nrp' => 'required|string|max:50|unique:users,nrp',
            'email' => 'nullable|email|unique:users,email',
            'pangkat' => 'nullable|string|max:20',
            'role' => 'required|in:admin,petugas,admin_subdit,pimpinan',
            'subdit' => [
                'nullable',
                'integer',
                'between:1,3',
                Rule::requiredIf(fn () => in_array($request->role, ['admin_subdit', 'petugas'])),
            ],
            'unit' => [
                'nullable',
                'integer',
                'between:1,5',
                Rule::requiredIf(fn () => $request->role === 'petugas'),
            ],
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama wajib diisi',
            'nrp.required' => 'NRP wajib diisi',
            'nrp.unique' => 'NRP sudah terdaftar',
            'role.required' => 'Role wajib dipilih',
            'role.in' => 'Role tidak valid',
            'subdit.required' => 'Subdit wajib dipilih untuk role ini',
            'subdit.between' => 'Subdit harus antara 1-3',
            'unit.required' => 'Unit wajib dipilih untuk role Petugas',
            'unit.between' => 'Unit harus antara 1-5',
        ]);

        // Set default password to NRP
        $validated['password'] = Hash::make($validated['nrp']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Clear subdit/unit based on role
        if ($validated['role'] === 'admin' || $validated['role'] === 'pimpinan') {
            $validated['subdit'] = null;
            $validated['unit'] = null;
        } elseif ($validated['role'] === 'admin_subdit') {
            $validated['unit'] = null;
        }

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan. Password default: NRP');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): InertiaResponse
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roleOptions' => self::getRoleOptions(),
            'subditOptions' => self::getSubditOptions(),
            'unitOptions' => self::getUnitOptions(),
            'pangkatOptions' => Pangkat::getDropdownOptions(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nrp' => ['required', 'string', 'max:50', Rule::unique('users', 'nrp')->ignore($user->id)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'pangkat' => 'nullable|string|max:20',
            'role' => 'required|in:admin,petugas,admin_subdit,pimpinan',
            'subdit' => [
                'nullable',
                'integer',
                'between:1,3',
                Rule::requiredIf(fn () => in_array($request->role, ['admin_subdit', 'petugas'])),
            ],
            'unit' => [
                'nullable',
                'integer',
                'between:1,5',
                Rule::requiredIf(fn () => $request->role === 'petugas'),
            ],
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
        ], [
            'name.required' => 'Nama wajib diisi',
            'nrp.required' => 'NRP wajib diisi',
            'nrp.unique' => 'NRP sudah terdaftar',
            'role.required' => 'Role wajib dipilih',
            'subdit.required' => 'Subdit wajib dipilih untuk role ini',
            'unit.required' => 'Unit wajib dipilih untuk role Petugas',
        ]);

        // Clear subdit/unit based on role
        if ($validated['role'] === 'admin' || $validated['role'] === 'pimpinan') {
            $validated['subdit'] = null;
            $validated['unit'] = null;
        } elseif ($validated['role'] === 'admin_subdit') {
            $validated['unit'] = null;
        }

        // Hash password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus');
    }

    /**
     * Reset user password to NRP.
     */
    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make($user->nrp),
        ]);

        return back()->with('success', 'Password berhasil direset ke NRP');
    }

    /**
     * Get role options for dropdown.
     */
    public static function getRoleOptions(): array
    {
        return [
            ['value' => 'admin', 'label' => 'Admin'],
            ['value' => 'petugas', 'label' => 'Petugas'],
            ['value' => 'admin_subdit', 'label' => 'Admin Subdit'],
            ['value' => 'pimpinan', 'label' => 'Pimpinan'],
        ];
    }

    /**
     * Get subdit options for dropdown.
     */
    public static function getSubditOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Subdit 1'],
            ['value' => 2, 'label' => 'Subdit 2'],
            ['value' => 3, 'label' => 'Subdit 3'],
        ];
    }

    /**
     * Get unit options for dropdown.
     */
    public static function getUnitOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Unit 1'],
            ['value' => 2, 'label' => 'Unit 2'],
            ['value' => 3, 'label' => 'Unit 3'],
            ['value' => 4, 'label' => 'Unit 4'],
            ['value' => 5, 'label' => 'Unit 5'],
        ];
    }
}
