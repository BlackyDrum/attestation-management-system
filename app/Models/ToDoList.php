<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToDoList extends Model
{
    use HasFactory;

    protected $table = "todos";

    protected $fillable = [
        'task',
        'checked',
        'creator_id'
    ];
}
