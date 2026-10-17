<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Lee las credenciales de las variables de entorno sin escribirlas en código duro.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        $name = env('ADMIN_NAME', 'Administrador DULCE chic');

        if (! $email || ! $password) {
            $this->command->warn('Aviso: ADMIN_EMAIL o ADMIN_PASSWORD no están configurados en el archivo .env.');
            $this->command->line('Para crear un administrador interactivamente, ejecuta: php artisan make:admin');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]
        );

        $this->command->info("Administrador [{$email}] sembrado exitosamente en la base de datos.");
    }
}
