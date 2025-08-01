<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@voiceai.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'company' => 'Voice AI',
            'bio' => 'System Administrator',
        ]);
    }
}
