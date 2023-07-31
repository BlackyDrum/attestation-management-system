<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHasCheckedTask extends Model
{
    use HasFactory;

    protected $table = "user_has_checked_task";

    protected $fillable = [
        'user_id',
        'task_id',
        'checked',
        'editor_id',
    ];
}
