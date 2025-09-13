<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usar DB directamente para evitar problemas con el modelo
        DB::table('users')->insert([
            [
                'name' => 'Juan Carlos',
                'apellidos' => 'Administrador López',
                'email' => 'admin@example.com',
                'dpi' => '1234567890123',
                'fecha_nacimiento' => '1990-01-15',
                'telefono' => '12345678',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'María Elena',
                'apellidos' => 'Usuario García',
                'email' => 'user@example.com',
                'dpi' => '9876543210987',
                'fecha_nacimiento' => '1995-05-20',
                'telefono' => '87654321',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pedro Luis',
                'apellidos' => 'Pérez Martínez',
                'email' => 'pedro@example.com',
                'dpi' => '1122334455667',
                'fecha_nacimiento' => '1988-03-10',
                'telefono' => '55443322',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ana Sofía',
                'apellidos' => 'González Rodríguez',
                'email' => 'ana@example.com',
                'dpi' => '7788990011223',
                'fecha_nacimiento' => '1992-07-25',
                'telefono' => '99887766',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Carlos Eduardo',
                'apellidos' => 'Admin Hernández',
                'email' => 'carlos@example.com',
                'dpi' => '5566778899001',
                'fecha_nacimiento' => '1985-12-08',
                'telefono' => '33221100',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
