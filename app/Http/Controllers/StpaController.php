<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Services\StpaFpdiService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * StpaController
 * 
 * Handles STPA (Surat Tanda Penerimaan Aduan) PDF generation
 * Uses FPDI to fill PDF template with laporan data
 */
class StpaController extends Controller
{
    /**
     * Eager load relationships needed for PDF generation
     */
    protected array $eagerLoads = [
        'pelapor.alamatKtp.provinsi',
        'pelapor.alamatKtp.kabupaten',
        'pelapor.alamatKtp.kecamatan',
        'pelapor.alamatKtp.kelurahan',
        'korban.orang',
        'tersangka.identitas',
        'kategoriKejahatan',
        'petugas',
    ];

    /**
     * Generate and download STPA PDF
     */
    public function cetakPdf(Request $request, int $id, StpaFpdiService $pdfService): Response
    {
        $laporan = Laporan::with($this->eagerLoads)->findOrFail($id);

        try {
            // Generate PDF binary content
            $pdfContent = $pdfService->generate($laporan);

            // Determine filename
            $stpaNumber = $laporan->nomor_stpa 
                ? str_replace('/', '-', $laporan->nomor_stpa)
                : 'DRAFT-' . $laporan->id;
            
            $filename = "STPA-{$stpaNumber}.pdf";

            // Return inline or attachment based on request
            $disposition = $request->input('download', false) 
                ? 'attachment' 
                : 'inline';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "{$disposition}; filename=\"{$filename}\"")
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Error generating STPA PDF', [
                'laporan_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Gagal generate PDF STPA: ' . $e->getMessage());
        }
    }

    /**
     * Preview STPA PDF in browser
     */
    public function preview(int $id, StpaFpdiService $pdfService): Response
    {
        $laporan = Laporan::with($this->eagerLoads)->findOrFail($id);

        try {
            $pdfContent = $pdfService->generate($laporan);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="preview.pdf"');

        } catch (\Exception $e) {
            Log::error('Error previewing STPA PDF', [
                'laporan_id' => $id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Gagal preview PDF STPA');
        }
    }

    /**
     * Download STPA PDF
     */
    public function download(int $id, StpaFpdiService $pdfService): Response
    {
        $laporan = Laporan::with($this->eagerLoads)->findOrFail($id);

        try {
            $pdfContent = $pdfService->generate($laporan);

            $stpaNumber = $laporan->nomor_stpa 
                ? str_replace('/', '-', $laporan->nomor_stpa)
                : 'DRAFT-' . $laporan->id;

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"STPA-{$stpaNumber}.pdf\"");

        } catch (\Exception $e) {
            Log::error('Error downloading STPA PDF', [
                'laporan_id' => $id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Gagal download PDF STPA');
        }
    }
}
