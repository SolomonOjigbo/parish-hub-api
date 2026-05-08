<?php

namespace Database\Seeders;

use App\Models\Society;
use Illuminate\Database\Seeder;

class SocietySeeder extends Seeder
{
    public function run(): void
    {
        $societies = [
            ['name' => 'Catholic Women Organisation (CWO)', 'slug' => 'cwo', 'colour' => '#8B0000'],
            ['name' => 'Catholic Men Organisation (CMO)', 'slug' => 'cmo', 'colour' => '#1A1A2E'],
            ['name' => 'Knights of St. Columba (KSC)', 'slug' => 'ksc', 'colour' => '#000080'],
            ['name' => 'St. Vincent de Paul', 'slug' => 'svdp', 'colour' => '#4169E1'],
            ['name' => 'Legion of Mary', 'slug' => 'legion-of-mary', 'colour' => '#4B0082'],
            ['name' => 'Youth Ministry', 'slug' => 'youth', 'colour' => '#E8541A'],
            ['name' => "Children's Liturgy Group", 'slug' => 'childrens-liturgy', 'colour' => '#FF8C00'],
            ['name' => 'Parish Choir', 'slug' => 'choir', 'colour' => '#2E7D32'],
            ['name' => 'Ushers Guild', 'slug' => 'ushers', 'colour' => '#5D4037'],
            ['name' => 'Altar Servers', 'slug' => 'altar-servers', 'colour' => '#C62828'],
        ];

        foreach ($societies as $society) {
            Society::firstOrCreate(['slug' => $society['slug']], $society);
        }
    }
}
