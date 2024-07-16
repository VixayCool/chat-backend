<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        for($i = 1; $i<=20; $i++){
            User::factory()->create([
                'name' => 'user'.$i,
                'email' => 'user'.$i.'@example.com',
                'password'=> 'password1234',
            ]);
        }
       
    }
}
