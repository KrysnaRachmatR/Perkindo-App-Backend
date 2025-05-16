<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Mas Inan',
            'username' => 'masinan',
            'email' => 'MinNan@gmail.com',
            'password' => 'MinNan123!', // password asli: minKrisno123!
        ]);
    }
}
