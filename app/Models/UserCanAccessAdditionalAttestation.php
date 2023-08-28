<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCanAccessAdditionalAttestation extends Model
{
    use HasFactory;

    protected $table = "user_can_access_additional_attestation";

    protected $fillable = [
        'user_id',
        'attestation_id',
    ];
}
