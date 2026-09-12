<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DriverSeeder::class,
            CitizenSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('Usuarios de prueba creados (contraseña para todos: password):');
        $this->command->info(' - admin@mototaxi.test        (admin)');
        $this->command->info(' - conductor1@mototaxi.test   (driver, aprobado)');
        $this->command->info(' - conductor2@mototaxi.test   (driver, aprobado)');
        $this->command->info(' - ciudadano1@mototaxi.test   (citizen)');
        $this->command->info(' - ciudadano2@mototaxi.test   (citizen)');
    }
}
