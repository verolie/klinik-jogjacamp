<?php

namespace App\Http\Controllers;

use App\Models\Diagnose;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function createService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:services,name',
        ], [
            'name.required' => 'Nama tidak boleh kosong',
            'name.unique' => 'Nama sudah terdaftar'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $name = $request->input('name');
            Service::create(['name' => $name]);

            return response()->json(['message' => "New Service Added: $name"], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
