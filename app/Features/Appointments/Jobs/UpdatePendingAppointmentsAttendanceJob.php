<?php

namespace App\Features\Appointments\Jobs;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\PaymentType;
use App\Models\Setting;
use Google\Service\Adsense\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UpdatePendingAppointmentsAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $appointmentIds = $this->getAppointments()->toArray();
        if (count($appointmentIds) > 0) {
            Appointment::whereIn('id', $appointmentIds)->update(['attendance_status' =>  Appointment::ASSIST_ATTENDANCE_STATUS, 'status' => Appointment::COMPLETED_STATUS]);
            $this->createPaymentAppointments($appointmentIds);
        }
    }

    private function getAppointments(): Collection
    {
        $now = Carbon::now();

        return Appointment::select('id')->where('attendance_status', 'pendiente')
            ->where(function ($query) use ($now) {
                $query->where('date', '<', $now->toDateString())
                    ->orWhere(function ($query) use ($now) {
                        $query->where('date', $now->toDateString())
                            ->where('end_time', '<=', $now->toTimeString());
                    });
            })
            ->pluck('id');

        return Appointment::select('id')->where('attendance_status', 'pendiente')
            ->where('date', '<=', $now->toDateString())
            ->where('end_time', '<=', $now->toTimeString())
            ->get()->pluck('id');
    }
    private function createPaymentAppointments(array $appointmentIds): void
    {
        $dataToInsert = [];
        $now = Carbon::now();
        $amount = Setting::select('value')->first();
        foreach ($appointmentIds as $appointmentId) {
            $dataToInsert[] = [
                'appointment_id' => $appointmentId,
                'amount' => $amount->value,
                'payment_type_id' => PaymentType::PAYMENT_TYPE_CASH_ID,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 1,
                'paid_at' => $now
            ];
        }
        AppointmentPayment::insert($dataToInsert);
    }
}
