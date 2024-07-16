<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Friendship;
class FriendshipTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i = 2; $i<=10; $i++){
            Friendship::create([
                'user_id'=>1,
                'friend_id'=>$i,
                'status'=>"pending",
            ]);
        }
        for($i = 11; $i<=20; $i++){
            Friendship::create([
                'user_id'=>1,
                'friend_id'=>$i,
                'status'=>"accepted",
            ]);
        }
        
        
       
    }
}
