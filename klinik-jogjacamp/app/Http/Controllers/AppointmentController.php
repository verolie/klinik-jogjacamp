<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CheckupProgress;
use App\Models\Diagnose;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{

    public function createAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'diagnose_id' => 'required|exists:diagnoses,id',
        ], [
            'patient_id.required' => 'Patient ID tidak boleh kosong',
            'diagnose_id.required' => 'Diagnose ID tidak boleh kosong',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $patient_id = $request->input('patient_id');
            $diagnose_id = $request->input('diagnose_id');

            $appointment = Appointment::create([
                'patient_id' => $patient_id,
                'diagnose_id' => $diagnose_id,
            ]);

            $diagnose = Diagnose::find($diagnose_id);
            $diagnoseName = $diagnose->name ?? null;

            if ($diagnoseName === "sakit ringan") {
                $this->createCheckupProgress($appointment->id, "obat");
            } elseif ($diagnoseName === "sakit berat") {
                $this->createCheckupProgress($appointment->id, "obat");
                $this->createCheckupProgress($appointment->id, "rawat inap");
            } elseif ($diagnoseName === "kritis") {
                $this->createCheckupProgress($appointment->id, "obat");
                $this->createCheckupProgress($appointment->id, "rawat inap");
                $this->createCheckupProgress($appointment->id, "icu");
            }

            return response()->json([
                'message' => "Appointment created successfully.",
                'appointment_id' => $appointment->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create appointment.'], 500);
        }
    }

    private function createCheckupProgress($appointmentId, $serviceName)
    {
        $serviceId = $this->getServiceIdByName($serviceName);
        if ($serviceId) {
            CheckupProgress::create([
                'appointment_id' => $appointmentId,
                'service_id' => $serviceId
            ]);
        }
    }

    private function getServiceIdByName($serviceName)
    {
        return optional(Service::where('name', $serviceName)->first())->id;
    }


    public function getAppointment($id)
    {
        try {
            $appointment = Appointment::with(['patient', 'diagnose', 'checkupProgress.service'])
                ->findOrFail($id);

            $responseData = [
                'id' => $appointment->id,
                'patient' => [
                    'id' => $appointment->patient->id,
                    'name' => $appointment->patient->name,
                ],
                'diagnose' => [
                    'id' => $appointment->diagnose->id,
                    'name' => $appointment->diagnose->name,
                ],
                'checkup' => $appointment->checkupProgress->map(function ($checkup) {
                    return [
                        'id' => $checkup->id,
                        'service' => [
                            'id' => $checkup->service->id,
                            'name' => $checkup->service->name,
                        ],
                        'status' => $checkup->status,
                    ];
                })
            ];

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Appointment not found or failed to retrieve.'], 404);
        }
    }


    public function patchAppointment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'diagnose_id' => 'required|exists:diagnoses,id',
            'status' => 'required',
        ], [
            'patient_id.required' => 'Patient ID tidak boleh kosong',
            'diagnose_id.required' => 'Diagnose ID tidak boleh kosong',
            'status.required' => 'Status tidak boleh kosong',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $appointment = Appointment::with('checkupProgress')->findOrFail($id);

            $allCompleted = $appointment->checkupProgress->every(function ($checkup) {
                return $checkup->status === 1;
            });

            if (!$allCompleted) {
                return response()->json(['error' => 'Cannot update appointment until all checkup processes are completed.'], 400);
            }

            $appointment->update([
                'patient_id' => $request->input('patient_id'),
                'diagnose_id' => $request->input('diagnose_id'),
            ]);

            foreach ($request->input('status') as $statusUpdate) {
                $checkupProgress = CheckupProgress::findOrFail($statusUpdate['id']);
                $checkupProgress->update([
                    'status' => $statusUpdate['status'],
                ]);
            }

            // Fetch updated appointment data with related models for the response
            $updatedAppointment = Appointment::with([
                'patient:id,name',
                'diagnose:id,name',
                'checkupProgress' => function ($query) {
                    $query->with('service:id,name')->select('id', 'appointment_id', 'service_id', 'status');
                }
            ])->findOrFail($id);

            return response()->json([
                'id' => $updatedAppointment->id,
                'patient' => [
                    'id' => $updatedAppointment->patient->id,
                    'name' => $updatedAppointment->patient->name,
                ],
                'diagnose' => [
                    'id' => $updatedAppointment->diagnose->id,
                    'name' => $updatedAppointment->diagnose->name,
                ],
                'checkup' => $updatedAppointment->checkupProgress->map(function ($checkup) {
                    return [
                        'id' => $checkup->id,
                        'service' => [
                            'id' => $checkup->service->id,
                            'name' => $checkup->service->name,
                        ],
                        'status' => $checkup->status,
                    ];
                }),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update appointment.'], 500);
        }
    }
}
