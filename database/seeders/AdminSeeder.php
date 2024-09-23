<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    public function run()
    {
        Admin::create([
            'name' => 'Mas Krisno', // Ganti dengan nama yang diinginkan
            'username' => 'minKrisno', // Ganti dengan username yang diinginkan
            'password' => 'minKrisno123', // Ganti dengan password yang diinginkan
        ]);
    }
}
