<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = ['name'];

    public function checkupProgress()
    {
        return $this->hasMany(CheckupProgress::class);
    }
}
