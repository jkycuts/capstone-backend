<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'firstname' => 'Super',
            'lastname' => 'Admin',
            'email' => 'super_admin@greengauge.com',
            'password' => Hash::make('admin123'), // change to a secure password later
            'role' => 'super_admin',
            
        ]);

        // Normal user
        User::create([
            'firstname' => 'Regular',
            'lastname' => 'User',
            'email' => 'user@greengauge.com',
            'password' => Hash::make('user1234'),
            'role' => 'user',
            'company_id' => null, // adjust accordingly
        ]);
    }
}
