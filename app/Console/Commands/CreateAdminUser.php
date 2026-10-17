<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin 
                            {--name= : Nombre del administrador} 
                            {--email= : Correo electrónico del administrador} 
                            {--password= : Contraseña (opcional, se solicitará de forma oculta si se omite)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea o actualiza de forma segura un usuario con privilegios de administrador';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre del administrador', 'Administrador DULCE chic');
        $email = $this->option('email') ?: $this->ask('Correo electrónico');

        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'email'],
        ]);

        if ($emailValidator->fails()) {
            $this->error('El correo electrónico ingresado no es válido.');
            return Command::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        $passwordValidator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($passwordValidator->fails()) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');
            return Command::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]
        );

        $this->info("¡Administrador [{$user->email}] configurado correctamente con privilegios de administración!");
        return Command::SUCCESS;
    }
}
