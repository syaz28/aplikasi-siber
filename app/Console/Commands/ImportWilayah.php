<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Import Wilayah Data from SQL File
 * 
 * Imports Indonesia administrative regions from cahyadsn/wilayah SQL dump
 * Source: https://github.com/cahyadsn/wilayah
 */
class ImportWilayah extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'wilayah:import 
                            {--file=database/wilayah.sql : Path to wilayah SQL file}
                            {--fresh : Truncate existing data before import}';

    /**
     * The console command description.
     */
    protected $description = 'Import Indonesia wilayah data from SQL file (cahyadsn/wilayah)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = base_path($this->option('file'));

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->info("Download from: https://github.com/cahyadsn/wilayah");
            return Command::FAILURE;
        }

        $this->info("📂 Reading file: {$filePath}");
        $this->info("📊 File size: " . $this->formatBytes(File::size($filePath)));

        // Read file content
        $sql = File::get($filePath);
        
        // Count approximate records
        $insertCount = substr_count($sql, "('");
        $this->info("📍 Estimated records: ~{$insertCount}");

        if (!$this->confirm("Proceed with import?", true)) {
            $this->warn("Import cancelled.");
            return Command::SUCCESS;
        }

        $this->info("");
        $this->info("🚀 Starting import...");

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate if fresh option
            if ($this->option('fresh')) {
                $this->info("🗑️  Truncating existing data...");
                DB::table('wilayah')->truncate();
            }

            // Remove DROP TABLE and CREATE TABLE statements, keep only INSERT
            $this->info("🔧 Processing SQL statements...");
            
            // Split by INSERT INTO statements
            $pattern = '/INSERT INTO\s+(?:`)?wilayah(?:`)?\s*\((?:`)?kode(?:`)?,\s*(?:`)?nama(?:`)?\)\s*VALUES\s*/i';
            $parts = preg_split($pattern, $sql);
            
            // Remove first part (contains DROP/CREATE)
            array_shift($parts);
            
            $totalInserted = 0;
            $bar = $this->output->createProgressBar(count($parts));
            $bar->start();

            foreach ($parts as $valuePart) {
                // Clean up the values part
                $valuePart = trim($valuePart);
                if (empty($valuePart)) continue;

                // Remove trailing semicolon and newlines
                $valuePart = rtrim($valuePart, ";\n\r ");
                
                // Parse the values
                $records = $this->parseValues($valuePart);
                
                if (!empty($records)) {
                    // Chunk insert to avoid memory issues
                    foreach (array_chunk($records, 1000) as $chunk) {
                        DB::table('wilayah')->insert($chunk);
                        $totalInserted += count($chunk);
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Get final count
            $finalCount = DB::table('wilayah')->count();

            $this->info("✅ Import completed successfully!");
            $this->info("📊 Total records in database: {$finalCount}");
            
            // Show summary by level
            $this->showSummary();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error("❌ Import failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Parse VALUES part of INSERT statement
     */
    private function parseValues(string $valuePart): array
    {
        $records = [];
        
        // Match each value tuple: ('kode','nama')
        preg_match_all("/\('([^']*)',\s*'([^']*)'\)/", $valuePart, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $records[] = [
                'kode' => $match[1],
                'nama' => $match[2],
            ];
        }

        return $records;
    }

    /**
     * Show summary of imported data
     */
    private function showSummary(): void
    {
        $this->newLine();
        $this->info("📊 Summary by Level:");
        
        // Provinsi (2 chars)
        $provinsi = DB::table('wilayah')->whereRaw('LENGTH(kode) = 2')->count();
        $this->info("   🏛️  Provinsi: {$provinsi}");
        
        // Kabupaten/Kota (5 chars)
        $kabupaten = DB::table('wilayah')->whereRaw('LENGTH(kode) = 5')->count();
        $this->info("   🏙️  Kabupaten/Kota: {$kabupaten}");
        
        // Kecamatan (8 chars)
        $kecamatan = DB::table('wilayah')->whereRaw('LENGTH(kode) = 8')->count();
        $this->info("   🏘️  Kecamatan: {$kecamatan}");
        
        // Kelurahan/Desa (13 chars)
        $kelurahan = DB::table('wilayah')->whereRaw('LENGTH(kode) = 13')->count();
        $this->info("   🏠 Kelurahan/Desa: {$kelurahan}");
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
