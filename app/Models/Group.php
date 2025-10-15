<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Message;
use App\Models\GroupProfile;
class Group extends Model
{
    use HasFactory;
    public $fillable = [
        "name",
    ];
    public function users(){
        return $this->belongsToMany(User::class, "group_users")->using(GroupUser::class)->withPivot("role");
    }
    public function messages(){
        return $this->hasMany(Message::class,"destination_id")->where("destination_type", "group");
    }
    public function latestMessage(){
        return $this->hasOne(Message::class, "destination_id")->where("destination_type", "group")->latest();
    }
    public function profile(){
        return $this->hasOne(GroupProfile::class, "group_id", "id");
    }
    
}
