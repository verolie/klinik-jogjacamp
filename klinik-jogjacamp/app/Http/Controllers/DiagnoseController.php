<?php

namespace App\Http\Controllers;

use App\Models\Diagnose;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiagnoseController extends Controller
{
    public function createDiagnose(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:diagnoses,name',
        ], [
            'name.required' => 'Nama tidak boleh kosong',
            'name.unique' => 'Nama sudah terdaftar'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $name = $request->input('name');
            Diagnose::create(['name' => $name]);

            return response()->json(['message' => "New Diagnose Added: $name"], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
