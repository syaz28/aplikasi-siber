<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PawasController - Pawas (Petugas Piket) Selection
 * 
 * Handles identity selection for petugas role users.
 */
class PawasController extends Controller
{
    /**
     * Show Pawas selection page
     */
    public function select(): Response
    {
        return Inertia::render('Pawas/Select');
    }

    /**
     * Search personels via AJAX (limit 5)
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        $personels = Personel::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('nama_lengkap', 'like', "%{$query}%")
                        ->orWhere('nrp', 'like', "%{$query}%")
                        ->orWhere('pangkat', 'like', "%{$query}%");
                });
            })
            ->orderBy('nama_lengkap')
            ->limit(5)
            ->get()
            ->map(function ($personel) {
                return [
                    'id' => $personel->id,
                    'nrp' => $personel->nrp,
                    'nama_lengkap' => $personel->nama_lengkap,
                    'pangkat' => $personel->pangkat,
                    'label' => "{$personel->nama_lengkap} - {$personel->pangkat} ({$personel->nrp})",
                ];
            });

        return response()->json([
            'personels' => $personels,
            'total' => Personel::when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('nama_lengkap', 'like', "%{$query}%")
                        ->orWhere('nrp', 'like', "%{$query}%")
                        ->orWhere('pangkat', 'like', "%{$query}%");
                });
            })->count(),
        ]);
    }

    /**
     * Store selected Pawas in session
     */
    public function store(Request $request)
    {
        $request->validate([
            'pawas_id' => 'required|exists:personels,id',
        ]);

        $personel = Personel::findOrFail($request->pawas_id);

        // Store in session
        session([
            'active_pawas_id' => $personel->id,
            'active_pawas_nrp' => $personel->nrp,
            'active_pawas_name' => $personel->nama_lengkap,
            'active_pawas_pangkat' => $personel->pangkat,
        ]);

        return redirect()->route('dashboard')->with('success', "Masuk sebagai {$personel->nama_lengkap}");
    }

    /**
     * Clear Pawas selection (switch identity)
     */
    public function clear()
    {
        session()->forget([
            'active_pawas_id',
            'active_pawas_nrp',
            'active_pawas_name',
            'active_pawas_pangkat',
        ]);

        return redirect()->route('pawas.select')->with('info', 'Silakan pilih identitas Pawas baru');
    }
}
