<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attestation extends Model
{
    use HasFactory;

    protected $table = "attestation";

    protected $fillable = [
      'subject_number',
      'subject_name',
      'semester_id',
      'creator_id'
    ];
}
