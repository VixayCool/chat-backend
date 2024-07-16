<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        "group_id",
        "bio",
        "profile_image",
        "background_image",
    ];

}
