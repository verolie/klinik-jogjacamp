<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DiagnoseController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;

Route::get('/token', function () {
    return csrf_token();
});

Route::middleware('api')->prefix('api')->group(function () {
    Route::post('/patient', [PatientController::class, 'createPatient']);
    Route::post('/diagnose', [DiagnoseController::class, 'createDiagnose']);
    Route::post('/service', [ServiceController::class, 'createService']);

    Route::post('/appointment', [AppointmentController::class, 'createAppointment']);
    Route::get('/appointment/{id}', [AppointmentController::class, 'getAppointment']);
    Route::patch('/appointment/{id}', [AppointmentController::class, 'patchAppointment']);
});