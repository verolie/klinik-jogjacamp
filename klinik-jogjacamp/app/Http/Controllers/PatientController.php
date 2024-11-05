<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    public function createPatient(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:patients,name',
        ], [
            'name.required' => 'Nama tidak boleh kosong',
            'name.unique' => 'Nama sudah terdaftar'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $name = $request->input('name');
            Patient::create(['name' => $name]);

            return response()->json(['message' => "New Patient Added: $name"], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
