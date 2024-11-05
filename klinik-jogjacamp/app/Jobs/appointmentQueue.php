<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\CheckupProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class AppointmentQueue implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue;

    public $appointment_id;

    public function __construct($appointment_id)
    {
        $this->appointment_id = $appointment_id;
    }

    public function handle()
    {
        Log::info('Processing appointment queue for appointment ID: ' . $this->appointment_id);

        $appointment = Appointment::find($this->appointment_id);

        if (!$appointment) {
            Log::error('Appointment not found for ID: ' . $this->appointment_id);
            return; // Exit if the appointment does not exist
        }

        $checkupProgress = CheckupProgress::where('appointment_id', $this->appointment_id)->get();

        if ($checkupProgress->isEmpty()) {
            Log::info('No checkup progress records found for appointment ID: ' . $this->appointment_id);
            return;
        }

        $allCompleted = true;

        foreach ($checkupProgress as $checkup) {
            CheckupProgress::where('id', $checkup->id)->update(['status' => 1]);
            Log::info('Checkup ID: ' . $checkup->id . ' updated to status: 1');
        }

        // Reload the checkup progress to verify their statuses
        $checkupProgress->each(function ($checkup) use (&$allCompleted) {
            if ($checkup->status !== 1) {
                $allCompleted = false; // Set to false if any checkup's status isn't 1
            }
        });

        if ($allCompleted) {
            $appointment->update(['status' => 'completed']);
            Log::info('Appointment completed: ' . $appointment->id);
        } else {
            Log::info('Not all checkups are completed for appointment ID: ' . $this->appointment_id);
        }
    }
}
