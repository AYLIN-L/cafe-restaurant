<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use RuntimeException;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    $password = env('ADMIN_PASSWORD');

    if (empty($password)) {
        throw new RuntimeException(
            'La variable ADMIN_PASSWORD est obligatoire.'
        );
    }
    User::firstOrCreate(
            ['email' => 'aylin.code@gmail.com'],
            [
                'name' => 'Administrateur',
                'phone' => '0619000500',
                'password' => $password,
                'role' => 'admin',
            ]
        );
    }
}
