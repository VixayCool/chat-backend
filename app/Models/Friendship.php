<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MorphToMany;

use App\Models\User;
use App\Models\Message;

class Friendship extends Model
{
    use HasFactory;
    protected $table = "friendships";
    protected $fillable =[
        'user_id',
        'friend_id',
        'status'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function messages(){
        return $this->hasMany(Message::class, 'destination_id')->where("destination_type", "user");
    }
    public function latestMessage(){
        return $this->hasOne(Message::class, 'destination_id')->where("destination_type", "user")->latest();
    }
}
