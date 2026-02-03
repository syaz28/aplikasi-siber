<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AdminPersonelController
 * 
 * CRUD operations for Personnel (Personel) management (Admin only)
 * This replaces the old User management for actual police personnel data.
 */
class AdminPersonelController extends Controller
{
    /**
     * Available Pangkat (Rank) options
     */
    public static function getPangkatOptions(): array
    {
        return [
            'JENDERAL', 'KOMJEN', 'IRJEN', 'BRIGJEN', 'KOMBES', 
            'AKBP', 'KOMPOL', 'AKP', 'IPTU', 'IPDA', 
            'AIPTU', 'AIPDA', 'BRIPKA', 'BRIGADIR', 'BRIPTU', 'BRIPDA'
        ];
    }

    /**
     * Available Subdit options
     */
    public static function getSubditOptions(): array
    {
        return [
            ['id' => 1, 'name' => 'Subdit 1'],
            ['id' => 2, 'name' => 'Subdit 2'],
            ['id' => 3, 'name' => 'Subdit 3'],
        ];
    }

    /**
     * Available Unit options
     */
    public static function getUnitOptions(): array
    {
        return [
            ['id' => 1, 'name' => 'Unit 1'],
            ['id' => 2, 'name' => 'Unit 2'],
            ['id' => 3, 'name' => 'Unit 3'],
            ['id' => 4, 'name' => 'Unit 4'],
            ['id' => 5, 'name' => 'Unit 5'],
        ];
    }

    /**
     * Display a listing of personels with pagination.
     */
    public function index(Request $request): InertiaResponse
    {
        $query = Personel::query()
            ->orderBy('nama_lengkap', 'asc');

        // Search by name or NRP
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nrp', 'like', "%{$search}%");
            });
        }

        // Filter by pangkat
        if ($request->filled('pangkat')) {
            $query->where('pangkat', $request->input('pangkat'));
        }

        // Filter by subdit
        if ($request->filled('subdit')) {
            $query->where('subdit_id', $request->input('subdit'));
        }

        $personels = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Personel/Index', [
            'personels' => $personels,
            'filters' => $request->only(['search', 'pangkat', 'subdit']),
            'pangkatOptions' => self::getPangkatOptions(),
            'subditOptions' => self::getSubditOptions(),
            'unitOptions' => self::getUnitOptions(),
        ]);
    }

    /**
     * Show the form for creating a new personel.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Personel/Create', [
            'pangkatOptions' => self::getPangkatOptions(),
            'subditOptions' => self::getSubditOptions(),
            'unitOptions' => self::getUnitOptions(),
        ]);
    }

    /**
     * Store a newly created personel.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nrp' => 'required|string|max:20|unique:personels,nrp',
            'nama_lengkap' => 'required|string|max:255',
            'pangkat' => 'nullable|string|max:20',
            'subdit_id' => 'nullable|integer|between:1,3',
            'unit_id' => 'nullable|integer|between:1,5',
        ], [
            'nrp.required' => 'NRP wajib diisi',
            'nrp.unique' => 'NRP sudah terdaftar',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
        ]);

        Personel::create($validated);

        return redirect()->route('admin.personels.index')
            ->with('success', 'Data personel berhasil ditambahkan');
    }

    /**
     * Show the form for editing the specified personel.
     */
    public function edit(Personel $personel): InertiaResponse
    {
        return Inertia::render('Admin/Personel/Edit', [
            'personel' => $personel,
            'pangkatOptions' => self::getPangkatOptions(),
            'subditOptions' => self::getSubditOptions(),
            'unitOptions' => self::getUnitOptions(),
        ]);
    }

    /**
     * Update the specified personel.
     */
    public function update(Request $request, Personel $personel)
    {
        $validated = $request->validate([
            'nrp' => [
                'required',
                'string',
                'max:20',
                Rule::unique('personels', 'nrp')->ignore($personel->id),
            ],
            'nama_lengkap' => 'required|string|max:255',
            'pangkat' => 'nullable|string|max:20',
            'subdit_id' => 'nullable|integer|between:1,3',
            'unit_id' => 'nullable|integer|between:1,5',
        ], [
            'nrp.required' => 'NRP wajib diisi',
            'nrp.unique' => 'NRP sudah terdaftar',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
        ]);

        $personel->update($validated);

        return redirect()->route('admin.personels.index')
            ->with('success', 'Data personel berhasil diperbarui');
    }

    /**
     * Remove the specified personel.
     */
    public function destroy(Personel $personel)
    {
        $personel->delete();

        return redirect()->route('admin.personels.index')
            ->with('success', 'Data personel berhasil dihapus');
    }
}
