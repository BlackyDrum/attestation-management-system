<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHasCheckedField extends Model
{
    use HasFactory;

    protected $table = "user_has_checked_field";

    protected $fillable = [
        'user_id',
        'field_id',
        'checked',
    ];
}
