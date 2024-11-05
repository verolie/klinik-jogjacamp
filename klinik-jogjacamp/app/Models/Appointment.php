<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $fillable = ['patient_id', 'diagnose_id'];

    /**
     * Relationship with the Patient model.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relationship with the Diagnose model.
     */
    public function diagnose()
    {
        return $this->belongsTo(Diagnose::class);
    }

    /**
     * Relationship with the CheckupProgress model.
     */
    public function checkupProgress()
    {
        return $this->hasMany(CheckupProgress::class);
    }

}
