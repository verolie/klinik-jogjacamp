<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CheckupProgress;
use App\Models\Diagnose;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\AppointmentQueue;
use Illuminate\Support\Facades\Log;

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

            AppointmentQueue::dispatch($appointment->id);

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
            $appointment = Appointment::join('patients', 'patients.id', '=', 'appointments.patient_id')
                ->join('diagnoses', 'diagnoses.id', '=', 'appointments.diagnose_id')
                ->where('appointments.id', '=', $id)
                ->select('appointments.id', 'patients.id as patient_id', 'patients.name as patient_name', 'diagnoses.id as diagnose_id', 'diagnoses.name as diagnose_name')
                ->first();

            if (!$appointment) {
                return response()->json(['error' => 'Appointment not found.'], 404);
            }

            $checkupProgress = CheckupProgress::join('services', 'services.id', '=', 'checkup_progress.service_id')
                ->where('checkup_progress.appointment_id', '=', $appointment->id)
                ->select('checkup_progress.id', 'services.id as service_id', 'services.name as service_name', 'checkup_progress.status')
                ->get();

            $responseData = [
                'id' => $appointment->id,
                'patient' => [
                    'id' => $appointment->patient_id,
                    'name' => $appointment->patient_name,
                ],
                'diagnose' => [
                    'id' => $appointment->diagnose_id,
                    'name' => $appointment->diagnose_name,
                ],
                'checkup' => $checkupProgress->map(function ($checkup) {
                    return [
                        'id' => $checkup->id,
                        'service' => [
                            'id' => $checkup->service_id,
                            'name' => $checkup->service_name,
                        ],
                        'status' => $checkup->status === 1 ? 'Completed' : 'In Progress',
                    ];
                }),
            ];

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve appointment data.'], 500);
        }
    }

    public function patchAppointment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'diagnose_id' => 'required|exists:diagnoses,id',
            'status' => 'required|in:0,1',
        ], [
            'patient_id.required' => 'Patient ID tidak boleh kosong',
            'diagnose_id.required' => 'Diagnose ID tidak boleh kosong',
            'status.required' => 'Status tidak boleh kosong',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $appointment = Appointment::join('patients', 'patients.id', '=', 'appointments.patient_id')
                ->join('diagnoses', 'diagnoses.id', '=', 'appointments.diagnose_id')
                ->where('appointments.id', $id)
                ->where('appointments.diagnose_id', $request->diagnose_id)
                ->where('appointments.patient_id', $request->patient_id)
                ->select('appointments.*', 'patients.id as patient_id', 'patients.name as patient_name', 'diagnoses.id as diagnose_id', 'diagnoses.name as diagnose_name')
                ->first();

            if (!$appointment) {
                return response()->json(['error' => 'Cannot find appointment data.'], 404);
            }

            $checkupProgress = CheckupProgress::where('appointment_id', $id)->get();

            $allCompleted = $checkupProgress->every(function ($checkup) {
                return $checkup->status === 1;
            });

            if (!$allCompleted) {
                return response()->json(['error' => 'Cannot update appointment until all checkup processes are completed.'], 400);
            }

            Appointment::where('id', $id)->update(['status' => $request->status]);

            $updatedAppointment = Appointment::join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->join('diagnoses', 'diagnoses.id', '=', 'appointments.diagnose_id')
            ->where('appointments.id', $id)
            ->select(
                'appointments.*',
                'patients.id as patient_id',
                'patients.name as patient_name',
                'diagnoses.id as diagnose_id',
                'diagnoses.name as diagnose_name'
            )
                ->first();

            $updatedCheckupProgress = CheckupProgress::join(
                'services',
                'services.id',
                '=',
                'checkup_progress.service_id'
            )
                ->where('checkup_progress.appointment_id', $updatedAppointment->id)
                ->select('checkup_progress.id', 'services.id as service_id', 'services.name as service_name', 'checkup_progress.status')
                ->get();
                

            return response()->json([
                'id' => $updatedAppointment->id,
                'patient' => [
                    'id' => $updatedAppointment->patient_id,
                    'name' => $updatedAppointment->patient_name,
                ],
                'diagnose' => [
                    'id' => $updatedAppointment->diagnose_id,
                    'name' => $updatedAppointment->diagnose_name,
                ],
                'checkup' => $updatedCheckupProgress->map(function ($checkup) {
                    return [
                        'id' => $checkup->id,
                        'service' => [
                            'id' => $checkup->service_id,
                            'name' => $checkup->service_name,
                        ],
                        'status' => $checkup->status === 1 ? 'Completed' : 'In Progress',
                    ];
                }),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update appointment.'], 500);
        }
    }

}
