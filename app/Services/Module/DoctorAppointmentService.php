<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Exceptions\BaseException;
use App\Http\Resources\Front\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorUnavailability;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DoctorAppointmentService
{
    /** user_id → doctor_id */
    private function doctorIdByUser(int $userId)
    {
        return Doctor::where('user_id', $userId)->value('id');
    }

    /** Kilidli statuslar: təsdiqlənmiş & tamamlanmış */
    private function lockedStatuses(): array
    {
        return [AppointmentStatusEnum::Confirmed, AppointmentStatusEnum::Completed];
    }

    /** List + filters */
    public function list(int $userId, array $filters): LengthAwarePaginator
    {
        $doctorId = $this->doctorIdByUser($userId);



        $q = Appointment::query()
            ->with([
                'patient.user',
                'clinic',
                'service'
            ])
            ->where('doctor_id', $doctorId);

        // search by patient name
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $q->whereHas('patient.user', function (Builder $uq) use ($term) {
                $uq->where('name', 'like', "%{$term}%")
                    ->orWhere('surname', 'like', "%{$term}%");
            });
        }

        // status filter (comma-separated)
        if (!empty($filters['status'])) {
            $statuses = array_filter(array_map('trim', explode(',', $filters['status'])));
            $q->whereIn('appointment_status', $statuses);
        }

        // date range
        if (!empty($filters['from'])) {
            $q->where('start_time', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (!empty($filters['to'])) {
            $q->where('start_time', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        // clinic
        if (!empty($filters['clinic_id'])) {
            $q->where('clinic_id', $filters['clinic_id']);
        }

        // sorting
        $sort = $filters['sort'] ?? '-start_time';
        if ($sort === 'start_time') {
            $q->orderBy('start_time', 'asc');
        } else {
            $q->oldest();
        }

        $perPage = (int)($filters['per_page'] ?? 10);

        return $q->paginate($perPage);
    }

    /** One */
    public function getOne(int $userId, int $id): Appointment
    {
        $doctorId = $this->doctorIdByUser($userId);

        return Appointment::with(['patient.user', 'clinic', 'service'])
            ->where('doctor_id', $doctorId)
            ->findOrFail($id);
    }

    /** Status dəyiş
     * @throws BaseException
     */
    public function updateStatus(int $userId, int $id, string $status): Appointment
    {
        $doctorId   = $this->doctorIdByUser($userId);
        $appointment= Appointment::where('doctor_id', $doctorId)->findOrFail($id);

        // Biznes qaydaları
        $current = $appointment->appointment_status;

        // Completed və Cancelled olan randevuların statusunu dəyişməyə icazə vermirik (reopen etmirsinizsə)
        if (in_array($current, [AppointmentStatusEnum::Completed, AppointmentStatusEnum::Cancelled], true)) {
            throw new BaseException(t('validation.appointment.cannot_change_finished'), 422);
        }

        // “no-show” yalnız görüş vaxtı keçəndən sonra verilə bilər (opsional qayda)
        if ($status === AppointmentStatusEnum::NoShow && Carbon::now()->lt(Carbon::parse($appointment->end_time))) {
            throw new BaseException(t('validation.appointment.noshow_only_after_end'), 422);
        }

        // “completed” yalnız görüş bitəndən sonra
        if ($status === AppointmentStatusEnum::Completed && Carbon::now()->lt(Carbon::parse($appointment->end_time))) {
            throw new BaseException(t('validation.appointment.complete_only_after_end'), 422);
        }

        // “confirmed” → gələcəkdə olmalıdır
        if ($status === AppointmentStatusEnum::Confirmed && Carbon::parse($appointment->start_time)->lt(now())) {
            throw new BaseException(t('validation.appointment.cannot_confirm_past'), 422);
        }

        $appointment->update(['appointment_status' => $status]);
        return $appointment->fresh(['patient.user','clinic','service']);
    }

    /** Reschedule
     * @throws BaseException
     */
    public function reschedule(int $userId, int $id, array $data): Appointment
    {
        $doctorId   = $this->doctorIdByUser($userId);
        $appointment= Appointment::where('doctor_id', $doctorId)->findOrFail($id);

        // keçmişi dəyişməyək
        if (Carbon::parse($appointment->start_time)->lt(now())) {
            throw new BaseException(t('validation.appointment.cannot_reschedule_past'), 422);
        }

        $newStart = Carbon::parse($data['start']);
        $newEnd   = Carbon::parse($data['end']);

        if ($newStart->lt(now())) {
            throw new BaseException(t('validation.appointment.cannot_reschedule_to_past'), 422);
        }

        // Həkimin həmin klinikada mövcudluğu + məşğulluq/başqa görüş overlap yoxlaması
        $clinicId = (int)($data['clinic_id'] ?? $appointment->clinic_id);

        // Busy (unavailability)
        $busyExists = DoctorUnavailability::query()
            ->where('doctor_id', $doctorId)
            ->where(function ($q) use ($newStart, $newEnd) {
                $q->whereBetween('start_time', [$newStart, $newEnd])
                    ->orWhereBetween('end_time',   [$newStart, $newEnd])
                    ->orWhere(function ($q2) use ($newStart, $newEnd) {
                        $q2->where('start_time', '<=', $newStart)->where('end_time', '>=', $newEnd);
                    });
            })
            ->exists();

        if ($busyExists) {
            throw new BaseException(t('validation.appointment.overlaps_unavailability'), 422);
        }

        // Başqa appointment overlap (özündən başqa)
        $overlap = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('id', '!=', $appointment->id)
            ->whereNotIn('appointment_status', [AppointmentStatusEnum::Cancelled, AppointmentStatusEnum::NoShow])
            ->where(function ($q) use ($newStart, $newEnd) {
                $q->whereBetween('start_time', [$newStart, $newEnd])
                    ->orWhereBetween('end_time',   [$newStart, $newEnd])
                    ->orWhere(function ($q2) use ($newStart, $newEnd) {
                        $q2->where('start_time', '<=', $newStart)->where('end_time', '>=', $newEnd);
                    });
            })
            ->exists();

        if ($overlap) {
            throw new BaseException(t('validation.appointment.overlaps_existing'), 422);
        }

        // Həkimin recurring availability-si ilə uyğunluq (opsional: əgər tələbdir)
        // Burada sadəcə klinika uyğunluğu və iş saatına düşməsini yoxlaya bilərsən; hazırda skip.

        $payload = [
            'start_time' => $newStart,
            'end_time'   => $newEnd,
            'clinic_id'  => $clinicId,
            'appointment_status' => AppointmentStatusEnum::Rescheduled,
        ];

        if (!empty($data['service_id'])) {
            $payload['service_id'] = (int)$data['service_id'];
        }

        $appointment->update($payload);

        return $appointment->fresh(['patient.user','clinic','service']);
    }

    /** Note update */
    public function updateNote(int $userId, int $id, ?string $note): Appointment
    {
        $doctorId   = $this->doctorIdByUser($userId);
        $appointment= Appointment::where('doctor_id', $doctorId)->findOrFail($id);
        $appointment->update(['notes' => $note]);
        return $appointment->fresh(['patient.user','clinic','service']);
    }

    /**
     * Appointment report (stats + list)
     */
    public function report(int $userId, array $filters): array
    {
        $doctorId = $this->doctorIdByUser($userId);

        $q = Appointment::query()
            ->with(['patient.user', 'clinic'])
            ->where('doctor_id', $doctorId);

        // filterlər
        if (!empty($filters['from'])) {
            $q->where('start_time', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (!empty($filters['to'])) {
            $q->where('start_time', '<=', Carbon::parse($filters['to'])->endOfDay());
        }
        if (!empty($filters['clinic_id'])) {
            $q->where('clinic_id', $filters['clinic_id']);
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $statuses = array_filter(array_map('trim', explode(',', $filters['status'])));
            $q->whereIn('appointment_status', $statuses);
        }

        // siyahı
        $perPage = (int)($filters['per_page'] ?? 10);
        $list = $q->orderBy('start_time', 'desc')->paginate($perPage);

        // statistikalar
        $stats = [
            'confirmed'        => Appointment::where('doctor_id', $doctorId)->where('appointment_status', AppointmentStatusEnum::Confirmed)->count(),
            'not_confirmed'    => Appointment::where('doctor_id', $doctorId)->where('appointment_status', AppointmentStatusEnum::Pending)->count(),
            'completed'        => Appointment::where('doctor_id', $doctorId)->where('appointment_status', AppointmentStatusEnum::Completed)->count(),
            'doctor_cancelled' => Appointment::where('doctor_id', $doctorId)->where('cancelled_by', 'doctor')->count(),
            'patient_cancelled'=> Appointment::where('doctor_id', $doctorId)->where('cancelled_by', 'patient')->count(),
            'no_show'          => Appointment::where('doctor_id', $doctorId)->where('appointment_status', AppointmentStatusEnum::NoShow)->count(),
        ];

        return [
            'stats' => $stats,
            'items' => AppointmentResource::collection($list),
            'meta'  => [
                'current_page' => $list->currentPage(),
                'per_page'     => $list->perPage(),
                'total'        => $list->total(),
                'last_page'    => $list->lastPage(),
            ]
        ];
    }
}
