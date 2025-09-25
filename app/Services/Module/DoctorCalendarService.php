<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Exceptions\BaseException;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorUnavailability;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorCalendarService
{
    /** Tarih aralığını formalaşdır
     * @throws BaseException
     */
    public function resolveDateRange(?string $from, ?string $to): array
    {
        $start = $from ? CarbonImmutable::parse($from)->startOfDay() : now()->startOfWeek();
        $end = $to ? CarbonImmutable::parse($to)->endOfDay() : now()->endOfWeek();

        if ($end->lt($start)) {
            throw new BaseException(t('validation.date_range.invalid'), 422);
        }
        return [$start, $end];
    }

    /** İstifadəçi → Doctor ID */
    private function doctorIdByUser(int $userId): int
    {
        return Doctor::where('user_id', $userId)->value('id');
    }

    /** “Kilidli” statuslar: təsdiqlənmiş və ya tamamlanmış */
    private function lockedStatuses(): array
    {
        return [
            AppointmentStatusEnum::Confirmed,
            AppointmentStatusEnum::Completed,
        ];
    }

    /** Overlap yoxlaması (appointments) */
    private function hasLockedAppointmentsOverlap(int $doctorId, Carbon $start, Carbon $end): bool
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('appointment_status', $this->lockedStatuses())
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            })
            ->exists();
    }

    private function getAppointmentsForDoctor(int $doctorId, $from, $to): Collection
    {
        return Appointment::query()
            ->with(['patient.user', 'clinic:id,name'])
            ->where('doctor_id', $doctorId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_time', [$from, $to])
                    ->orWhereBetween('end_time', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('start_time', '<=', $from)
                            ->where('end_time', '>=', $to);
                    });
            })
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'type' => 'appointment',
                'start' => $a->start_time,
                'end' => $a->end_time,
                'status' => $a->appointment_status,
                'clinic_name' => $a->clinic?->name,
                'title' => trim(($a->patient?->user?->name ?? '') . ' ' . ($a->patient?->user?->surname ?? '')),
                'patient' => [
                    'fullname' => $a->patient->user->fullname,
                    'photo' => $a->patient->user->photo,
                    'email' => $a->patient->user->email,
                    'phone' => $a->patient->user->phone,
                ],
                'service' => $a->service->name,
            ]);
    }

    private function getScheduleForDoctor(int $doctorId, Carbon $from, Carbon $to): Collection
    {
        // Schedules (recurring availability) — aralığa düşən occurence-ləri generasiya edirik
        $schedules = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('end_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('start_date', '<=', $from->toDateString())
                            ->where('end_date', '>=', $to->toDateString());
                    });
            })
            ->get();

        $scheduleEvents = collect();
        foreach ($schedules as $s) {
            // Sadə: weekly üçün days-of-week massivinə görə periodu iterasiya edək
            $range = CarbonPeriod::create(
                Carbon::parse($s->start_date)->startOfDay(),
                Carbon::parse($s->end_date)->endOfDay()
            )->filter(function (Carbon $d) use ($s, $from, $to) {
                // hər "every N week" filtrini və seçilmiş günləri nəzərə alırıq
                if ($d->lt($from) || $d->gt($to)) return false;
                if ($s->frequency === 'weekly') {
                    $days = is_array($s->days) ? $s->days : json_decode($s->days ?? '[]', true);
                    return in_array((int)$d->dayOfWeek, $days, true) && $this->weekStepMatches($s->start_date, $d, (int)$s->every);
                }
                // daily/monthly ssenariləri lazım olduqca genişlənə bilər
                return true;
            });

            foreach ($range as $d) {
                $start = Carbon::parse($d->toDateString() . ' ' . $s->from_time->format('H:i:s'));
                $end = Carbon::parse($d->toDateString() . ' ' . $s->to_time->format('H:i:s'));
                $scheduleEvents->push([
                    'id' => $s->id,
                    'type' => 'schedule',
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString(),
                    'clinic_name' => $s->clinic?->name ?? null,
                    'title' => 'Available',
                ]);
            }
        }

        // Unavailability
        $busy = DoctorUnavailability::query()
            ->where('doctor_id', $doctorId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_time', [$from, $to])
                    ->orWhereBetween('end_time', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('start_time', '<=', $from)
                            ->where('end_time', '>=', $to);
                    });
            })
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'type' => 'unavailability',
                'start' => $u->start_time,
                'end' => $u->end_time,
                'title' => $u->note ?? 'Busy',
            ]);

        return $scheduleEvents->concat($busy)->values();
    }

    /** Kalendar feed: appointments + schedules + unavailability */
    public function getCalendarFeed(int $userId, $from, $to): array
    {
        $doctorId = $this->doctorIdByUser($userId);

        $requestType = request()->get('is_agency');

        if ($requestType === 'true') {
            return [
                'items' => $this->getAppointmentsForDoctor($doctorId, $from, $to)
            ];
        } else {
            return [
                'items' => $this->getScheduleForDoctor($doctorId, $from, $to)
            ];
        }
    }

    /** Weekly step helper: start_date-dən etibarən hər N həftə uyğun gəlirmi */
    private function weekStepMatches(string $startDate, Carbon $candidate, int $every): bool
    {
        $start = Carbon::parse($startDate)->startOfWeek();
        $diff = $start->diffInWeeks($candidate->copy()->startOfWeek());
        return $diff % max($every, 1) === 0;
    }

    /** Saat interval overlap? (HH:ii ilə) */
    private function timeRangeOverlaps(string $fromA, string $toA, string $fromB, string $toB): bool
    {
        // "A_start < B_end && A_end > B_start" klassik yoxlama
        return ($fromA < $toB) && ($toA > $fromB);
    }

    /** Tarix aralığı overlap? (Y-m-d ilə) */
    private function dateRangeOverlaps(string $startA, string $endA, string $startB, string $endB): bool
    {
        return ($startA <= $endB) && ($endA >= $startB);
    }

    /** Weekly üçün gün kəsişməsi var? */
    private function weekDaysIntersect(?array $daysA, ?array $daysB): bool
    {
        $a = is_array($daysA) ? $daysA : [];
        $b = is_array($daysB) ? $daysB : [];
        return count(array_intersect($a, $b)) > 0;
    }

    /**
     * Mövcud recurring-lərlə overlap varmı?
     * - eyni həkim
     * - eyni klinika
     * - tarix aralığı kəsişir
     * - vaxt aralığı kəsişir
     * - weekly üçün günlər kəsişir
     */
    private function hasRecurringScheduleOverlap(
        int   $doctorId,
        int   $clinicId,
        array $data,
        ?int  $excludeId = null
    ): bool
    {
        $q = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('clinic_id', $clinicId);

        if (!empty($excludeId)) {
            $q->where('id', '!=', $excludeId);
        }

        // əvvəlcə tarix aralığı üzrə kəsişənləri götürək
        $candidates = $q->where(function ($q2) use ($data) {
            $q2->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                ->orWhere(function ($q3) use ($data) {
                    $q3->where('start_date', '<=', $data['start_date'])
                        ->where('end_date', '>=', $data['end_date']);
                });
        })
            ->get();

        foreach ($candidates as $ex) {
            // vaxt kəsişməsi yoxsa keç
            if (!$this->timeRangeOverlaps($data['from_time'], $data['to_time'], $ex->from_time, $ex->to_time)) {
                continue;
            }

            // frequency fərqli ola bilər, minimal qayda: weekly üçün günlər kəsişməlidir
            if (($data['frequency'] === 'weekly') || ($ex->frequency === 'weekly')) {
                if (!$this->weekDaysIntersect($data['days'] ?? [], $ex->days ?? [])) {
                    continue;
                }
            }

            // Bu nöqtəyə gəlmişiksə, eyni klinikada tarix+vaxt (və weekly-də gün) üzrə overlap var
            return true;
        }

        return false;
    }

    /** Həkimin bu klinikada işlədiyini yoxla
     * @throws BaseException
     */
    private function assertDoctorHasClinic(int $doctorId, int $clinicId): void
    {
        $exists = Doctor::query()
            ->where('id', $doctorId)
            ->whereHas('clinics', fn($q) => $q->where('clinics.id', $clinicId))
            ->exists();

        if (!$exists) {
            throw new BaseException(t('validation.calendar.clinic_not_assigned_to_doctor'), 422);
        }
    }

    private function hasLockedAppointmentsForRecurringDelete(int $doctorId, DoctorSchedule $rec): bool
    {
        $now = Carbon::now()->startOfDay();
        $startDate = Carbon::parse($rec->start_date)->startOfDay();
        $endDate = Carbon::parse($rec->end_date)->endOfDay();

        // Yalnız gələcək hissəni nəzərə alaq
        $windowStart = $now->greaterThan($startDate) ? $now : $startDate;

        // Əvvəlcə həmin aralıqda eyni klinikada kilidli görüşlər varmı?
        $locked = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('clinic_id', $rec->clinic_id)
            ->whereIn('appointment_status', $this->lockedStatuses()) // Confirmed və Completed
            ->where(function ($q) use ($windowStart, $endDate) {
                $q->whereBetween('start_time', [$windowStart, $endDate])
                    ->orWhereBetween('end_time', [$windowStart, $endDate])
                    ->orWhere(function ($q2) use ($windowStart, $endDate) {
                        $q2->where('start_time', '<=', $windowStart)
                            ->where('end_time', '>=', $endDate);
                    });
            })
            ->get(['id', 'start_time', 'end_time']);

        if ($locked->isEmpty()) {
            return false;
        }

        // Occurence-ləri tarix üzrə gəz
        $period = \Carbon\CarbonPeriod::create($windowStart, $endDate);
        $days = is_array($rec->days) ? $rec->days : (json_decode($rec->days ?? '[]', true) ?: []);

        foreach ($period as $d) {
            // yalnız uyğun günləri burax
            if ($rec->frequency === 'weekly') {
                if (!in_array((int)$d->dayOfWeek, $days, true)) {
                    continue;
                }
                if (!$this->weekStepMatches($rec->start_date, $d, (int)$rec->every)) {
                    continue;
                }
            }
            // (daily/monthly üçün eyni şablon genişlənə bilər; hazırda weekly əsas ssenaridir)

            $occStart = Carbon::parse($d->toDateString() . ' ' . $rec->from_time);
            $occEnd = Carbon::parse($d->toDateString() . ' ' . $rec->to_time);

            foreach ($locked as $a) {
                // Klassik vaxt overlap: A_start < B_end && A_end > B_start
                if ($occStart->lt($a->end_time) && $occEnd->gt($a->start_time)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasUnavailabilityOverlap(int $doctorId, Carbon $start, Carbon $end, ?int $excludeId = null): bool
    {
        $q = DoctorUnavailability::query()
            ->where('doctor_id', $doctorId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            });

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->exists();
    }

    /** Recurring availability create
     * @throws BaseException
     */
    public function createRecurringAvailability(int $userId, array $data)
    {
        $doctorId = $this->doctorIdByUser($userId);

        // Keçmişə qadağa
        if (Carbon::parse($data['start_date'])->lt(now()->startOfDay())) {
            throw new BaseException(t('validation.calendar.cannot_create_in_past'), 422);
        }

        // Həkim bu klinikada işləməlidir
        $this->assertDoctorHasClinic($doctorId, (int)$data['clinic_id']);

        // Locked görüşlə overlap
        $this->assertNoLockedOverlapForRecurring($doctorId, $data);

        // >>> YENİ: Mövcud recurring-lərlə overlapı qadağan et
        if ($this->hasRecurringScheduleOverlap($doctorId, (int)$data['clinic_id'], $data, null)) {
            throw new BaseException(t('validation.calendar.overlaps_existing_availability'), 422);
        }

        return DoctorSchedule::create([
            'doctor_id' => $doctorId,
            'clinic_id' => $data['clinic_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'from_time' => $data['from_time'],
            'to_time' => $data['to_time'],
            'frequency' => $data['frequency'],
            'every' => $data['every'],
            'days' => $data['days'] ?? [],
        ]);
    }

    /** Recurring availability update
     * @throws BaseException
     */
    public function updateRecurringAvailability(int $userId, int $id, array $data)
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $rec = DoctorSchedule::where('doctor_id', $doctor->id)->findOrFail($id);

        // Keçmişi dəyişmək olmaz
        if (Carbon::parse($data['start_date'])->lt(now()->startOfDay())) {
            throw new BaseException(t('validation.calendar.cannot_edit_past'), 422);
        }

        // Həkim bu klinikada işləməlidir
        $this->assertDoctorHasClinic($doctor->id, (int)$data['clinic_id']);

        // Locked overlap qadağası
        $this->assertNoLockedOverlapForRecurring($doctor->id, $data);

        // Mövcud recurring-lərlə overlapı qadağan et (özünü istisna et)
        if ($this->hasRecurringScheduleOverlap($doctor->id, (int)$data['clinic_id'], $data, $rec->id)) {
            throw new BaseException(t('validation.calendar.overlaps_existing_availability'), 422);
        }

        $rec->update([
            'clinic_id' => $data['clinic_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'from_time' => $data['from_time'],
            'to_time' => $data['to_time'],
            'frequency' => $data['frequency'],
            'every' => $data['every'],
            'days' => $data['days'] ?? [],
        ]);

        return $rec;
    }

    /** Recurring availability delete
     * @throws BaseException
     */
    public function deleteRecurringAvailability(int $userId, int $id): void
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $rec = DoctorSchedule::where('doctor_id', $doctor->id)->findOrFail($id);

        // Keçmişdəki periodu silmək olmaz (tam keçmişsə)
        if (Carbon::parse($rec->start_date)->lt(now()->startOfDay())) {
            throw new BaseException(t('validation.calendar.cannot_delete_past'), 422);
        }

        // Gələcək occurence-lərlə Confirmed/Completed görüş kəsişməsi varsa — silmə!
        if ($this->hasLockedAppointmentsForRecurringDelete($doctor->id, $rec)) {
            throw new BaseException(t('validation.calendar.overlaps_locked_appointment'), 422);
        }

        $rec->delete();
    }

    /** Unavailability create
     * @throws BaseException
     */
    public function createUnavailability(int $userId, array $data)
    {
        $doctorId = $this->doctorIdByUser($userId);

        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);

        if ($start->lt(now())) {
            throw new BaseException(t('validation.calendar.cannot_create_in_past'), 422);
        }

        // 1) Confirmed/Completed appointment ilə kəsişmə – qadağa
        if ($this->hasLockedAppointmentsOverlap($doctorId, $start, $end)) {
            throw new BaseException(t('validation.calendar.overlaps_locked_appointment'), 422);
        }

        // 2) Mövcud unavailability ilə kəsişmə – qadağa (eyni gün/eyni saat bir neçəsini yaratmasın)
        if ($this->hasUnavailabilityOverlap($doctorId, $start, $end)) {
            throw new BaseException(t('validation.calendar.overlaps_unavailability'), 422);
        }

        return DoctorUnavailability::create([
            'doctor_id' => $doctorId,
            'start_time' => $start,
            'end_time' => $end,
            'note' => $data['note'] ?? null,
        ]);
    }

    /** Unavailability delete
     * @throws BaseException
     */
    public function deleteUnavailability(int $userId, int $id): void
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $item = DoctorUnavailability::where('doctor_id', $doctor->id)->findOrFail($id);

        if (Carbon::parse($item->start_time)->lt(now())) {
            throw new BaseException(t('validation.calendar.cannot_delete_past'), 422);
        }

        if ($this->hasLockedAppointmentsOverlap($doctor->id,
            Carbon::parse($item->start_time),
            Carbon::parse($item->end_time)
        )) {
            throw new BaseException(t('validation.calendar.overlaps_locked_appointment'), 422);
        }

        $item->delete();
    }

    /** Appointment status update (agenda) */
    public function updateAppointmentStatus(int $userId, int $appointmentId, $status)
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();

        $appointment = Appointment::where('doctor_id', $doctor->id)->findOrFail($appointmentId);

        // Status `AppointmentStatusEnum`-dan gəlməlidir — burada sadəcə set edirik
        $appointment->update(['appointment_status' => $status]);

        return $appointment->fresh();
    }

    /** Recurring üçün overlap yoxlaması
     * @throws BaseException
     */
    private function assertNoLockedOverlapForRecurring(int $doctorId, array $data): void
    {
        // Aralıqdakı seçilmiş günləri iterasiya edirik və hər gün üçün [from_time, to_time] intervalını yoxlayırıq
        $days = $data['days'] ?? [];
        $period = CarbonPeriod::create(
            Carbon::parse($data['start_date'])->startOfDay(),
            Carbon::parse($data['end_date'])->endOfDay()
        );

        foreach ($period as $d) {
            // weekly isə yalnız seçilmiş günlər + every N həftə
            if (($data['frequency'] ?? 'weekly') === 'weekly') {
                if (!in_array((int)$d->dayOfWeek, $days, true)) continue;
                if (!$this->weekStepMatches($data['start_date'], $d, (int)$data['every'])) continue;
            }

            $start = Carbon::parse($d->toDateString() . ' ' . $data['from_time']);
            $end = Carbon::parse($d->toDateString() . ' ' . $data['to_time']);

            if ($this->hasLockedAppointmentsOverlap($doctorId, $start, $end)) {
                throw new BaseException(t('validation.calendar.overlaps_locked_appointment'), 422);
            }
        }
    }
}
