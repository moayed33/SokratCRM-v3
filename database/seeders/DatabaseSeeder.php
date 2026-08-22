<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            CrmV2PipelineSeeder::class,
            CrmAccessControlSeeder::class,
            CalendarEventSeeder::class,
        ]);
    }
}
