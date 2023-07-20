<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttestationTasks extends Model
{
    use HasFactory;

    protected $table = "attestation_tasks";

    protected $fillable = [
        'attestation_id',
        'title',
        'description',
    ];
}
