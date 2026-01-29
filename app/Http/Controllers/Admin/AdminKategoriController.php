<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriKejahatan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AdminKategoriController
 * 
 * CRUD operations for Kategori Kejahatan (Admin only)
 */
class AdminKategoriController extends Controller
{
    /**
     * Display a listing of kategori kejahatan.
     */
    public function index(Request $request): InertiaResponse
    {
        $query = KategoriKejahatan::withCount('laporan')
            ->orderBy('nama', 'asc');

        // Search by nama
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $kategori = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Kategori/Index', [
            'kategori' => $kategori,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new kategori.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Kategori/Create');
    }

    /**
     * Store a newly created kategori.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100|unique:kategori_kejahatan,nama',
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ], [
            'nama.required' => 'Nama kategori wajib diisi',
            'nama.unique' => 'Nama kategori sudah ada',
            'nama.max' => 'Nama kategori maksimal 100 karakter',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        KategoriKejahatan::create($validated);

        return redirect()->route('admin.kategori.index')
            ->with('success', 'Kategori kejahatan berhasil ditambahkan');
    }

    /**
     * Show the form for editing the specified kategori.
     */
    public function edit(KategoriKejahatan $kategori): InertiaResponse
    {
        return Inertia::render('Admin/Kategori/Edit', [
            'kategori' => $kategori,
        ]);
    }

    /**
     * Update the specified kategori.
     */
    public function update(Request $request, KategoriKejahatan $kategori)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100|unique:kategori_kejahatan,nama,' . $kategori->id,
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ], [
            'nama.required' => 'Nama kategori wajib diisi',
            'nama.unique' => 'Nama kategori sudah ada',
            'nama.max' => 'Nama kategori maksimal 100 karakter',
        ]);

        $kategori->update($validated);

        return redirect()->route('admin.kategori.index')
            ->with('success', 'Kategori kejahatan berhasil diperbarui');
    }

    /**
     * Remove the specified kategori.
     */
    public function destroy(KategoriKejahatan $kategori)
    {
        // Check if kategori is being used by any laporan
        if ($kategori->laporan()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh ' . $kategori->laporan()->count() . ' laporan');
        }

        $kategori->delete();

        return redirect()->route('admin.kategori.index')
            ->with('success', 'Kategori kejahatan berhasil dihapus');
    }

    /**
     * Toggle active status of kategori.
     */
    public function toggleStatus(KategoriKejahatan $kategori)
    {
        $kategori->update(['is_active' => !$kategori->is_active]);

        $status = $kategori->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return back()->with('success', "Kategori berhasil {$status}");
    }
}
