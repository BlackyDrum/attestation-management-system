<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttestationFields extends Model
{
    use HasFactory;

    protected $table = "attestation_fields";

    protected $fillable = [
        'attestation_id',
        'title',
        'description',
    ];
}
