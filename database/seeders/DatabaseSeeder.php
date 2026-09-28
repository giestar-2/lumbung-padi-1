<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }
        $this->call([EmployeeSeeder::class, CustomerSeeder::class, ProductSeeder::class]);
    }
}
