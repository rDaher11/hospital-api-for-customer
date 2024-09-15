<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutineTest extends Model
{
    use HasFactory;

    protected $fillable = [
        "doctor_id",
        "patient_id",
        "breathing_rate",
        "body_temperature",
        "pulse_rate",
        "medical_notes",
        "prescription"
    ];

    public function doctor() {
        return $this->belongsTo(Doctor::class , "doctor_id" , "user_id");
    }

    public function patient() {
        return $this->belongsTo(User::class , "patient_id" , "id");
    }
}
