<?php

namespace App\Imports;

use App\Models\Personel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

/**
 * PersonelImport
 * 
 * Import class for DAPERS Excel file format.
 * 
 * File Structure:
 * - Header Row: Row 8 (Index 7)
 * - Data starts: Row 9
 * - Column B (Index 1): NO
 * - Column C (Index 2): NAMA
 * - Column D (Index 3): PANGKAT/NRP (merged)
 * - Column E (Index 4): JABATAN
 */
class PersonelImport implements ToModel, WithStartRow
{
    /**
     * Counter for successfully imported rows
     */
    public int $importedCount = 0;
    
    /**
     * Counter for skipped rows
     */
    public int $skippedCount = 0;

    /**
     * Start reading from row 9 (data rows)
     * Row 8 is header, Row 9+ is data
     */
    public function startRow(): int
    {
        return 9;
    }

    /**
     * Map Excel row to Personel model
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Column C (Index 2) = NAMA
        // Column D (Index 3) = PANGKAT/NRP (merged)
        $nama = isset($row[2]) ? trim($row[2]) : null;
        $pangkatNrp = isset($row[3]) ? trim($row[3]) : null;
        $jabatan = isset($row[4]) ? trim($row[4]) : null;
        
        // Skip invalid rows (empty nama or empty pangkat/nrp)
        // This also skips section headers like "SUBBAGRENMIN", "SUBDIT I"
        if (empty($nama) || empty($pangkatNrp)) {
            $this->skippedCount++;
            return null;
        }
        
        // Skip if nama looks like a header (contains only uppercase without numbers)
        if ($this->isLikelyHeader($nama)) {
            $this->skippedCount++;
            return null;
        }
        
        // Parse PANGKAT/NRP from merged column
        // Format: "AKP/84020101" or "PENDA / '1987..." or "BRIPKA / 87100620"
        [$pangkat, $nrp] = $this->parsePangkatNrp($pangkatNrp);
        
        // Skip if we couldn't extract valid data
        if (empty($pangkat) && empty($nrp)) {
            $this->skippedCount++;
            return null;
        }
        
        // Clean NRP: remove quotes, newlines, extra spaces
        $nrp = $this->cleanNrp($nrp);
        
        // Skip if NRP already exists (avoid duplicates)
        if (!empty($nrp) && Personel::where('nrp', $nrp)->exists()) {
            $this->skippedCount++;
            return null;
        }
        
        $this->importedCount++;
        
        return new Personel([
            'nama_lengkap' => $nama,
            'pangkat' => strtoupper(trim($pangkat)),
            'nrp' => $nrp,
            // jabatan is not in fillable, but we can add it if needed
            // 'jabatan' => $jabatan,
            'subdit_id' => null, // Will be assigned manually later
            'unit_id' => null,
        ]);
    }
    
    /**
     * Parse the merged PANGKAT/NRP column
     *
     * @param string $value e.g. "AKP/84020101" or "PENDA / '1987..."
     * @return array [pangkat, nrp]
     */
    private function parsePangkatNrp(string $value): array
    {
        // Try to split by "/" 
        $parts = explode('/', $value, 2);
        
        if (count($parts) >= 2) {
            return [
                trim($parts[0]),  // Pangkat
                trim($parts[1]),  // NRP
            ];
        }
        
        // If no separator found, treat whole value as pangkat (no NRP)
        return [trim($value), ''];
    }
    
    /**
     * Clean NRP value
     *
     * @param string $nrp
     * @return string
     */
    private function cleanNrp(string $nrp): string
    {
        // Remove single quotes
        $nrp = str_replace("'", '', $nrp);
        
        // Remove newlines and carriage returns
        $nrp = str_replace(["\n", "\r"], '', $nrp);
        
        // Remove extra spaces
        $nrp = trim($nrp);
        
        // Remove any non-alphanumeric characters except dots and dashes
        $nrp = preg_replace('/[^a-zA-Z0-9.-]/', '', $nrp);
        
        return $nrp;
    }
    
    /**
     * Check if a name looks like a section header
     *
     * @param string $nama
     * @return bool
     */
    private function isLikelyHeader(string $nama): bool
    {
        // Section headers are usually all uppercase and short
        $headerPatterns = [
            'SUBBAGRENMIN',
            'SUBDIT',
            'UNIT',
            'KANIT',
            'KASUBBAG',
            'KASUBDIT',
        ];
        
        foreach ($headerPatterns as $pattern) {
            if (Str::startsWith(strtoupper($nama), $pattern)) {
                return true;
            }
        }
        
        // If name is less than 5 chars and all uppercase, likely a header
        if (strlen($nama) < 5 && strtoupper($nama) === $nama) {
            return true;
        }
        
        return false;
    }
}
