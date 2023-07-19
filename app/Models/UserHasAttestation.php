<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHasAttestation extends Model
{
    use HasFactory;

    protected $table = "user_has_attestation";

    protected $fillable = [
      'user_id',
      'attestation_id',
    ];
}
