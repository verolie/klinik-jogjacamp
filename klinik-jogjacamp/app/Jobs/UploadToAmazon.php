<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AppointmentQueue implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function handle()
    {
        $updatedAppointment = $this->appointment->load('checkupProgress');

        $allCompleted = $updatedAppointment->checkupProgress->every(function ($checkup) {
            return $checkup->status === 1;
        });

        if ($allCompleted) {
            $updatedAppointment->update(['status' => 'completed']);
        }
    }
}
