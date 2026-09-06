<?php

namespace Database\Seeders;

use App\Models\Society;
use Illuminate\Database\Seeder;

class SocietySeeder extends Seeder
{
    public function run(): void
    {
        $societies = [
            ['name' => 'Catholic Women Organisation (CWO)', 'short_name' => 'CWO', 'slug' => 'cwo', 'colour' => '#8B0000'],
            ['name' => 'Catholic Men Organisation (CMO)', 'short_name' => 'CMO', 'slug' => 'cmo', 'colour' => '#1A1A2E'],
            ['name' => 'Knights of St. Columba (KSC)', 'short_name' => 'KSC', 'slug' => 'ksc', 'colour' => '#000080'],
            ['name' => 'St. Vincent de Paul', 'short_name' => 'SVP', 'slug' => 'svdp', 'colour' => '#4169E1'],
            ['name' => 'Legion of Mary', 'short_name' => 'Legion', 'slug' => 'legion-of-mary', 'colour' => '#4B0082'],
            ['name' => 'Youth Ministry', 'short_name' => 'Youth', 'slug' => 'youth', 'colour' => '#E8541A'],
            ['name' => "Children's Liturgy Group", 'short_name' => 'CLG', 'slug' => 'childrens-liturgy', 'colour' => '#FF8C00'],
            ['name' => 'Parish Choir', 'short_name' => 'Choir', 'slug' => 'choir', 'colour' => '#2E7D32'],
            ['name' => 'Ushers Guild', 'short_name' => 'Ushers', 'slug' => 'ushers', 'colour' => '#5D4037'],
            ['name' => 'Altar Servers', 'short_name' => 'Servers', 'slug' => 'altar-servers', 'colour' => '#C62828'],
        ];

        foreach ($societies as $society) {
            Society::updateOrCreate(['slug' => $society['slug']], $society);
        }
    }
}
