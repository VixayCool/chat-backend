<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
class Message extends Model
{
    use HasFactory;
    public $fillable = [
        'sender_id',
        'content',
        'destination_id',
        'destination_type',

    ];
    public function sender(){
        return $this->belongsTo(User::class, "sender_id");
    }
}
