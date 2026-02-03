<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder
 * 
 * Calls all seeders in correct dependency order
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🌱 Starting database seeding...');
        $this->command->info('================================');

        $this->call([
            // Master Data (no dependencies)
            WilayahSeeder::class,
            PangkatSeeder::class,
            MasterPlatformSeeder::class, // Platform for identitas tersangka
            MasterCountrySeeder::class,  // Countries and phone codes for WNA and phone input
            
            // Crime categories (no dependencies)
            KategoriKejahatanSeeder::class,
            
            // User accounts (shared accounts for login)
            UserSeeder::class,
            
            // Personnel data (for Pawas selection)
            PersonelSeeder::class,
        ]);

        $this->command->info('================================');
        $this->command->info('✅ Database seeding completed!');
        $this->command->info('');
    }
}
