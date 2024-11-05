<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Diagnose extends Model
{
    use HasFactory;
    protected $table = 'diagnoses';

    protected $fillable = ['name'];
}
