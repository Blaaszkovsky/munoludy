<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            Edition2026Seeder::class,
            PageContent2026Seeder::class,
        ]);
    }
}
