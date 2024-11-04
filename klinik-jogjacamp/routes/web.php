<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DiagnoseController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;

Route::post('/api/patient', [PatientController::class, 'createPatient']);
Route::post('/api/diagnose', [DiagnoseController::class, 'createDiagnose']);
Route::post('/api/service', [ServiceController::class, 'createService']);


Route::post('/api/appointment', [AppointmentController::class, 'createAppointment']);
Route::get('/api/appointment/{id}', [AppointmentController::class, 'getAppointment']);

Route::patch('/api/appointment/{id}', [AppointmentController::class, 'patchAppointment']);