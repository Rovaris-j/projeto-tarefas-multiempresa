<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run()
    {
        $companies = [
            ['name' => 'Alice in Chains', 'slug' => 'aic'],
            ['name' => 'Beatles', 'slug' => 'beat'],
            ['name' => 'Cream', 'slug' => 'cam'],
            ['name' => 'Death', 'slug' => 'dth'],
        ];

        foreach ($companies as $c) {
            Company::updateOrCreate(
                ['slug' => $c['slug']],
                ['name' => $c['name']]
            );
        }
    }
}