<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add 3 users to the database
        for($index = 1; $index <= 5; $index++){
            User::create([
                'username' => "user$index",
                'email' => "user$index@gmail.com",
                'password' => bcrypt('Admin123'),
                'email_verified_at' => Carbon::now(),
                'active' => true
            ]);
        }
    }
}
