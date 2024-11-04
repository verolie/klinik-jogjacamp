<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiagnosesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('diagnoses')->insert([
            ['name' => 'Sakit Ringan'],
            ['name' => 'Sakit Berat'],
            ['name' => 'Kritis']
        ]);
    }
}
