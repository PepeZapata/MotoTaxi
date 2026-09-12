<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = [
            [
                'email' => 'conductor1@mototaxi.test',
                'name' => 'Carlos Gómez',
                'phone' => '9990000002',
                'license_number' => 'LIC-0001',
                'plate' => 'MTX-001',
                'model' => 'Italika 150',
                'color' => 'Rojo',
                'lat' => 20.9674,
                'lng' => -89.5926, // Mérida, centro
            ],
            [
                'email' => 'conductor2@mototaxi.test',
                'name' => 'Miguel Torres',
                'phone' => '9990000003',
                'license_number' => 'LIC-0002',
                'plate' => 'MTX-002',
                'model' => 'Honda CG150',
                'color' => 'Negro',
                'lat' => 20.9738,
                'lng' => -89.6155,
            ],
        ];

        foreach ($drivers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'role' => 'driver',
                    'is_active' => true,
                ]
            );

            $driverProfile = $user->driverProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'license_number' => $data['license_number'],
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'availability_status' => 'available',
                ]
            );

            $driverProfile->vehicles()->updateOrCreate(
                ['plate' => $data['plate']],
                [
                    'model' => $data['model'],
                    'color' => $data['color'],
                    'is_active' => true,
                ]
            );

            $driverProfile->location()->updateOrCreate(
                ['driver_profile_id' => $driverProfile->id],
                [
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'updated_at_gps' => now(),
                ]
            );

            $driverProfile->shifts()->updateOrCreate(
                ['driver_profile_id' => $driverProfile->id, 'ended_at' => null],
                ['started_at' => now()]
            );
        }
    }
}
