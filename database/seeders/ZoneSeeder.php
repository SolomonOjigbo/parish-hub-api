<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['name' => 'Zone A', 'description' => 'Ikeja/Agege axis'],
            ['name' => 'Zone B', 'description' => 'Ipaja/Ayobo axis'],
            ['name' => 'Zone C', 'description' => 'Alimosho axis'],
            ['name' => 'Zone D', 'description' => 'Iyana-Ipaja axis'],
            ['name' => 'Zone E', 'description' => 'Boys Town/Abesan axis'],
            ['name' => 'Zone F', 'description' => 'Satellite Town axis'],
        ];

        foreach ($zones as $zone) {
            Zone::firstOrCreate(['name' => $zone['name']], $zone);
        }
    }
}
