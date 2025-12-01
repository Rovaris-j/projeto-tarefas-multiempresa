<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
  public function run()
    {
        $users = [
            ['company_slug' => 'aic', 'name' => 'Layne Staley', 'email' => 'layne@aic.com', 'password' => '1234'],
            ['company_slug' => 'aic', 'name' => 'Jerry Cantrell', 'email' => 'jerry@aic.com', 'password' => '12345'],
            ['company_slug' => 'beat', 'name' => 'John Lennon', 'email' => 'john@beat.com', 'password' => '123456'],
            ['company_slug' => 'beat', 'name' => 'Paul McCartney', 'email' => 'paul@beat.com', 'password' => '1234567'],
            ['company_slug' => 'cam', 'name' => 'Eric Clapton', 'email' => 'eric@cam.com', 'password' => '12345678'],
            ['company_slug' => 'cam', 'name' => 'Ginger Baker', 'email' => 'ginger@cam.com', 'password' => '123456789'],
            ['company_slug' => 'dth', 'name' => 'Chuck Schuldiner', 'email' => 'chuck@dth.com', 'password' => '1234567890'],
            ['company_slug' => 'dth', 'name' => 'Richard Christy', 'email' => 'richard@dth.com', 'password' => '12345678900'],
        ];

        foreach ($users as $u) {
            $company = Company::where('slug', $u['company_slug'])->first();

            // pula se a company não existir (evita erro em db:seed)
            if (!$company) {
                continue;
            }

            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                    'company_id' => $company->id,
                ]
            );
        }
    }
}