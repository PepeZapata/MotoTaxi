<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CitizenSeeder extends Seeder
{
    public function run(): void
    {
        $citizens = [
            ['email' => 'ciudadano1@mototaxi.test', 'name' => 'Ana López', 'phone' => '9990000004'],
            ['email' => 'ciudadano2@mototaxi.test', 'name' => 'Luis Ramírez', 'phone' => '9990000005'],
        ];

        foreach ($citizens as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'role' => 'citizen',
                    'is_active' => true,
                ]
            );

            $user->citizenProfile()->updateOrCreate(['user_id' => $user->id], []);
        }
    }
}
