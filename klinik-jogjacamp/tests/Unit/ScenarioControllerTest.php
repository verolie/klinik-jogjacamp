<?php

namespace Tests\Unit;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DiagnoseController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ServiceController;
use App\Models\Appointment;
use App\Models\CheckupProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Jobs\AppointmentQueue;

class ScenarioControllerTest extends TestCase
{
    use RefreshDatabase;

    protected PatientController $patientController;
    protected DiagnoseController $diagnoseController;
    protected ServiceController $serviceController;
    protected AppointmentController $appointmentController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patientController = new PatientController();
        $this->diagnoseController = new DiagnoseController();
        $this->serviceController = new ServiceController();
        $this->appointmentController = new AppointmentController();
    }

    public function test_patient_diagnose_service_appointment_flow()
    {
        // Create Patients
        $patients = [
            ['name' => 'Budi'],
            ['name' => 'Indah'],
            ['name' => 'Siska'],
        ];

        foreach ($patients as $patient) {
            $request = Request::create('/api/patient', 'POST', $patient);
            $response = $this->patientController->createPatient($request);

            $this->assertEquals(201, $response->getStatusCode());
            $this->assertJsonStringEqualsJsonString(
                json_encode(['message' => "New Patient Added: {$patient['name']}"]),
                $response->getContent()
            );
        }

        // Create Diagnoses
        $diagnoses = [
            ['name' => 'sakit ringan'],
            ['name' => 'sakit berat'],
            ['name' => 'kritis'],
        ];

        foreach ($diagnoses as $diagnose) {
            $request = Request::create('/api/diagnose', 'POST', $diagnose);
            $response = $this->diagnoseController->createDiagnose($request);

            $this->assertEquals(201, $response->getStatusCode());
            $this->assertJsonStringEqualsJsonString(
                json_encode(['message' => "New Diagnose Added: {$diagnose['name']}"]),
                $response->getContent()
            );
        }

        // Create Services
        $services = [
            ['name' => 'Obat'],
            ['name' => 'Rawat Inap'],
            ['name' => 'ICU'],
        ];

        foreach ($services as $service) {
            $request = Request::create('/api/service', 'POST', $service);
            $response = $this->serviceController->createService($request);

            $this->assertEquals(201, $response->getStatusCode());
            $this->assertJsonStringEqualsJsonString(
                json_encode(['message' => "New Service Added: {$service['name']}"]),
                $response->getContent()
            );
        }

        // Create Appointments
        $appointments = [
            [
                'patient_id' => 1, // Budi
                'diagnose_id' => 1, // Ringan
            ],
            [
                'patient_id' => 2, // Indah
                'diagnose_id' => 2, // Berat
            ],
            [
                'patient_id' => 3, // Siska
                'diagnose_id' => 3, // Kritis
            ],
        ];

        foreach ($appointments as $index => $appointment) {
            $request = Request::create('/api/appointment', 'POST', $appointment);
            $response = $this->appointmentController->createAppointment($request);

            $this->assertEquals(201, $response->getStatusCode());

            // Use the content() method to get the JSON content as an array
            $responseData = json_decode($response->getContent(), true); // Decode as an associative array
            $this->assertNotNull($responseData["appointment_id"]);

            if ($index === 1) {
                $appointmentData = Appointment::join('patients', 'patients.id', '=', 'appointments.patient_id')
                    ->join('diagnoses', 'diagnoses.id', '=', 'appointments.diagnose_id')
                    ->where('appointments.id', '=', $responseData["appointment_id"])
                    ->select('appointments.id', 'patients.id as patient_id', 'patients.name as patient_name', 'diagnoses.id as diagnose_id', 'diagnoses.name as diagnose_name')
                    ->first();

                $this->assertEquals($appointment['patient_id'], $appointmentData->patient_id);
                $this->assertEquals($appointment['diagnose_id'], $appointmentData->diagnose_id);

                $checkupProgress = CheckupProgress::join('services', 'services.id', '=', 'checkup_progress.service_id')
                    ->where('checkup_progress.appointment_id', '=', $appointmentData->id)
                    ->select('checkup_progress.id', 'services.id as service_id', 'services.name as service_name', 'checkup_progress.status')
                    ->get();

                $this->assertCount(2, $checkupProgress);
                $this->assertEquals($checkupProgress[0]->service_id, 1);
            }
        }

        sleep(5);

        foreach ($appointments as $index => $appointment) {
            $appointmentId = $index + 1; // Assuming IDs start from 1
            $job = new AppointmentQueue($appointmentId);
            $job->handle();

            $request = Request::create("/api/appointment/{$appointmentId}", 'PATCH', [
                'patient_id' => $appointment['patient_id'],
                'diagnose_id' => $appointment['diagnose_id'],
                'status' => 1,
            ]);

            $response = $this->appointmentController->patchAppointment($request, $appointmentId);
            $this->assertEquals(200, $response->getStatusCode());

            $actualResponse = json_decode($response->getContent(), true);

            $expectedCheckup = [];

            foreach ($actualResponse['checkup'] as $checkup) {
                $expectedCheckup[] = [
                    'id' => $checkup['id'],
                    'service' => [
                        'id' => $checkup['service']['id'],
                        'name' => $checkup['service']['name'],
                    ],
                    'status' => $checkup['status'],
                ];
            }

            $this->assertJsonStringEqualsJsonString(
                json_encode([
                    'id' => $appointmentId,
                    'patient' => [
                        'id' => $appointment['patient_id'],
                        'name' => $patients[$appointment['patient_id'] - 1]['name'],
                    ],
                    'diagnose' => [
                        'id' => $appointment['diagnose_id'],
                        'name' => $diagnoses[$appointment['diagnose_id'] - 1]['name'],
                    ],
                    'checkup' => $expectedCheckup, // Use the hash map you created
                ]),
                $response->getContent()
            );
        }
    }
}
