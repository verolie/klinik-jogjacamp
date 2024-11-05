<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CheckupProgress extends Model
{
    use HasFactory;
    protected $table = 'checkup_progress';

    protected $fillable = ['appointment_id', 'service_id'];
}
