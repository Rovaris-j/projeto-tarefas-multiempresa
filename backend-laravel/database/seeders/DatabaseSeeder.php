<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seed in order to satisfy foreign keys
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            TaskSeeder::class,
        ]);
    }
}
