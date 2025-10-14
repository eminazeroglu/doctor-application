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
use Illuminate\Support\Facades\Log;

class DoctorCalendarService
{
    /**
     * Tarix aralığını formalaşdırır
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

    /**
     * İstifadəçi → Doctor ID
     */
    private function doctorIdByUser(int $userId): int
    {
        $doctorId = Doctor::where('user_id', $userId)->value('id');

        if (!$doctorId) {
            throw new BaseException(t('validation.doctor.not_found'), 404);
        }

        return $doctorId;
    }

    /**
     * "Kilidli" statuslar: təsdiqlənmiş və tamamlanmış
     */
    private function lockedStatuses(): array
    {
        return [
            AppointmentStatusEnum::Confirmed,
            AppointmentStatusEnum::Completed,
        ];
    }

    /**
     * Kalendar feed: appointments + schedules + unavailability
     * Backend BÜTÜN event-ləri göndərir, frontend filter edir
     */
    public function getCalendarFeed(int $userId, $from, $to): array
    {
        $doctorId = $this->doctorIdByUser($userId);

        $appointments = $this->getAppointmentsForDoctor($doctorId, $from, $to);
        $schedules = $this->getSchedulesForDoctor($doctorId, $from, $to);
        $unavailabilities = $this->getUnavailabilitiesForDoctor($doctorId, $from, $to);

        $allEvents = $appointments
            ->concat($schedules)
            ->concat($unavailabilities)
            ->sortBy('start')
            ->values();

        return [
            'events' => $allEvents,
            'summary' => [
                'total' => $allEvents->count(),
                'appointments' => $appointments->count(),
                'schedules' => $schedules->count(),
                'unavailabilities' => $unavailabilities->count(),
            ]
        ];
    }

    /**
     * Həkimin appointment-lərini əldə edir
     */
    private function getAppointmentsForDoctor(int $doctorId, $from, $to): Collection
    {
        return Appointment::query()
            ->with(['patient.user', 'clinic', 'service'])
            ->where('doctor_id', $doctorId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_time', [$from, $to])
                    ->orWhereBetween('end_time', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('start_time', '<=', $from)
                            ->where('end_time', '>=', $to);
                    });
            })
            ->whereNotIn('appointment_status', [
                AppointmentStatusEnum::Cancelled,
                AppointmentStatusEnum::NoShow
            ])
            ->orderBy('start_time')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'type' => 'appointment',
                'start' => $a->start_time->format('Y-m-d H:i:s'),
                'end' => $a->end_time->format('Y-m-d H:i:s'),
                'status' => $a->appointment_status,
                'title' => trim(($a->patient?->user?->name ?? '') . ' ' . ($a->patient?->user?->surname ?? '')),
                'patient' => [
                    'fullname' => $a->patient?->user?->fullname,
                    'photo' => $a->patient?->user?->photo,
                    'email' => $a->patient?->user?->email,
                    'phone' => $a->patient?->user?->phone,
                ],
                'service' => $a->service?->name,
                'clinic_id' => $a->clinic_id,
                'clinic_name' => $a->clinic?->name,
            ]);
    }

    /**
     * Həkimin recurring schedule-lərini əldə edir
     */
    private function getSchedulesForDoctor(int $doctorId, $from, $to): Collection
    {
        $schedules = DoctorSchedule::query()
            ->with('clinic')
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $from->toDateString());
            })
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $scheduleEvents = collect();

        foreach ($schedules as $schedule) {
            $scheduleStart = Carbon::parse($schedule->start_date)->startOfDay();
            $scheduleEnd = $schedule->end_date
                ? Carbon::parse($schedule->end_date)->endOfDay()
                : $to->copy()->addYear()->endOfDay();

            $windowStart = $from->greaterThan($scheduleStart) ? $from : $scheduleStart;
            $windowEnd = $to->lessThan($scheduleEnd) ? $to : $scheduleEnd;

            $period = CarbonPeriod::create($windowStart->toDateString(), $windowEnd->toDateString());

            foreach ($period as $date) {
                // Exception dates yoxlaması
                if ($this->isExceptionDate($schedule, $date)) {
                    continue;
                }

                // Frequency matching
                if (!$this->isScheduleValidForDate($schedule, $date)) {
                    //continue;
                }

                $startTime = $this->parseScheduleTime($schedule->from_time, $date);
                $endTime = $this->parseScheduleTime($schedule->to_time, $date);

                if (!$startTime || !$endTime || $endTime->lte($startTime)) {
                    continue;
                }

                $scheduleEvents->push([
                    'id' => $schedule->id,
                    'type' => 'schedule',
                    'start' => $startTime->format('Y-m-d H:i:s'),
                    'end' => $endTime->format('Y-m-d H:i:s'),
                    'title' => 'Available',
                    'clinic_id' => $schedule->clinic_id,
                    'clinic_name' => $schedule->clinic?->name,
                    'frequency' => $schedule->frequency,
                    'is_recurring' => true,
                ]);
            }
        }

        return $scheduleEvents;
    }

    /**
     * Həkimin məşğulluq intervallarını əldə edir
     */
    private function getUnavailabilitiesForDoctor(int $doctorId, $from, $to): Collection
    {
        return DoctorUnavailability::query()
            ->where('doctor_id', $doctorId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_time', [$from, $to])
                    ->orWhereBetween('end_time', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('start_time', '<=', $from)
                            ->where('end_time', '>=', $to);
                    });
            })
            ->orderBy('start_time')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'type' => 'unavailability',
                'start' => $u->start_time->format('Y-m-d H:i:s'),
                'end' => $u->end_time->format('Y-m-d H:i:s'),
                'title' => 'Busy',
                'note' => $u->note,
                'clinic_id' => $u->clinic_id,
            ]);
    }

    /**
     * Schedule time-ı datetime-a çevirir
     */
    private function parseScheduleTime($timeValue, Carbon $date): ?Carbon
    {
        try {
            if ($timeValue instanceof Carbon) {
                return $date->copy()->setTime(
                    $timeValue->hour,
                    $timeValue->minute,
                    $timeValue->second
                );
            }

            if (is_string($timeValue)) {
                $parts = explode(':', $timeValue);
                $hour = (int)($parts[0] ?? 0);
                $minute = (int)($parts[1] ?? 0);
                $second = (int)($parts[2] ?? 0);

                if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                    return $date->copy()->setTime($hour, $minute, $second);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Schedule time parsing error: " . $e->getMessage(), [
                'time_value' => $timeValue,
                'date' => $date->toDateString()
            ]);
            return null;
        }
    }

    /**
     * Tarixin exception list-də olub-olmadığını yoxlayır
     */
    private function isExceptionDate(DoctorSchedule $schedule, Carbon $date): bool
    {
        $exceptions = $schedule->exception_dates ?? [];
        return in_array($date->toDateString(), $exceptions);
    }

    /**
     * Schedule-in müəyyən tarixə uyğun olub-olmadığını yoxlayır
     */
    private function isScheduleValidForDate(DoctorSchedule $schedule, Carbon $date): bool
    {
        $frequency = $schedule->frequency ?? 'weekly';

        return match ($frequency) {
            'daily' => $this->isValidDaily($schedule, $date),
            'weekly' => $this->isValidWeekly($schedule, $date),
            'monthly' => $this->isValidMonthly($schedule, $date),
            default => false,
        };
    }

    private function isValidDaily(DoctorSchedule $schedule, Carbon $date): bool
    {
        $startDate = Carbon::parse($schedule->start_date)->startOfDay();
        $daysDiff = $startDate->diffInDays($date->copy()->startOfDay());
        $every = max($schedule->every ?? 1, 1);

        return $daysDiff % $every === 0;
    }

    private function isValidWeekly(DoctorSchedule $schedule, Carbon $date): bool
    {
        $startDate = Carbon::parse($schedule->start_date)->startOfWeek();
        $checkDate = $date->copy()->startOfWeek();
        $weeksDiff = $startDate->diffInWeeks($checkDate);
        $every = max($schedule->every ?? 1, 1);

        if ($weeksDiff % $every !== 0) {
            return false;
        }

        $days = is_array($schedule->days) ? $schedule->days : [];

        if (empty($days)) {
            return true;
        }

        return in_array($date->dayOfWeek, $days);
    }

    private function isValidMonthly(DoctorSchedule $schedule, Carbon $date): bool
    {
        $startDate = Carbon::parse($schedule->start_date)->startOfMonth();
        $monthsDiff = $startDate->diffInMonths($date->copy()->startOfMonth());
        $every = max($schedule->every ?? 1, 1);

        if ($monthsDiff % $every !== 0) {
            return false;
        }

        return $startDate->day === $date->day;
    }

    /**
     * Recurring availability yaradır
     * @throws BaseException
     */
    public function createRecurringAvailability(int $userId, array $data)
    {
        $doctorId = $this->doctorIdByUser($userId);

        // ✅ Frequency validation
        $this->validateFrequencyData($data);

        if (Carbon::parse($data['start_date'])->lt(now()->startOfDay())) {
            throw new BaseException(t('validation.calendar.cannot_create_in_past'), 422);
        }

        $this->assertDoctorHasClinic($doctorId, (int)$data['clinic_id']);
        $this->assertNoLockedOverlapForRecurring($doctorId, $data);

        if ($this->hasRecurringScheduleOverlap($doctorId, (int)$data['clinic_id'], $data, null)) {
            throw new BaseException(t('validation.calendar.overlaps_existing_availability'), 422);
        }

        return DoctorSchedule::create([
            'doctor_id' => $doctorId,
            'clinic_id' => $data['clinic_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'from_time' => $data['from_time'],
            'to_time' => $data['to_time'],
            'frequency' => $data['frequency'],
            'every' => $data['every'],
            'days' => $data['days'] ?? [],
            'is_active' => true,
        ]);
    }

    /**
     * Frequency data validation
     */
    private function validateFrequencyData(array $data): void
    {
        $frequency = $data['frequency'] ?? 'weekly';

        switch ($frequency) {
            case 'daily':
                // Daily üçün days field olmamalıdır
                if (!empty($data['days'])) {
                    throw new BaseException(t('validation.calendar.daily_cannot_have_days'), 422);
                }
                break;

            case 'weekly':
                // Weekly üçün days MÜTLƏQ olmalıdır
                if (empty($data['days']) || !is_array($data['days'])) {
                    throw new BaseException(t('validation.calendar.weekly_requires_days'), 422);
                }

                // Days 0-6 arasında olmalıdır
                foreach ($data['days'] as $day) {
                    if ($day < 0 || $day > 6) {
                        throw new BaseException(t('validation.calendar.invalid_day_of_week'), 422);
                    }
                }
                break;

            case 'monthly':
                // Monthly üçün days field olmamalıdır
                if (!empty($data['days'])) {
                    throw new BaseException(t('validation.calendar.monthly_cannot_have_days'), 422);
                }
                break;
        }
    }

    /**
     * Recurring availability yeniləyir
     * @throws BaseException
     */
    public function updateRecurringAvailability(int $userId, int $id, array $data)
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $rec = DoctorSchedule::where('doctor_id', $doctor->id)->findOrFail($id);

        // ✅ Frequency validation
        $this->validateFrequencyData($data);

        if (Carbon::parse($data['start_date'])->lt(now()->startOfDay())) {
            throw new BaseException(t('validation.calendar.cannot_edit_past'), 422);
        }

        $this->assertDoctorHasClinic($doctor->id, (int)$data['clinic_id']);
        $this->assertNoLockedOverlapForRecurring($doctor->id, $data);

        if ($this->hasRecurringScheduleOverlap($doctor->id, (int)$data['clinic_id'], $data, $rec->id)) {
            throw new BaseException(t('validation.calendar.overlaps_existing_availability'), 422);
        }

        $rec->update([
            'clinic_id' => $data['clinic_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'from_time' => $data['from_time'],
            'to_time' => $data['to_time'],
            'frequency' => $data['frequency'],
            'every' => $data['every'],
            'days' => $data['days'] ?? [],
        ]);

        return $rec;
    }

    /**
     * Recurring availability silir (bütün schedule)
     */
    public function deleteRecurringAvailability(int $userId, int $id): void
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $rec = DoctorSchedule::where('doctor_id', $doctor->id)->findOrFail($id);

        if (Carbon::parse($rec->start_date)->lt(now()->startOfDay())) {
            throw new BaseException(t('validation.calendar.cannot_delete_past'), 422);
        }

        if ($this->hasLockedAppointmentsForRecurringDelete($doctor->id, $rec)) {
            throw new BaseException(t('validation.calendar.overlaps_locked_appointment'), 422);
        }

        $rec->delete();
    }

    /**
     * Konkret occurrence-i silir (exception_dates-ə əlavə edir)
     */
    public function deleteOccurrence(int $userId, int $scheduleId, string $date): void
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)->findOrFail($scheduleId);

        $date = Carbon::parse($date)->toDateString();

        if (Carbon::parse($date)->lt(now()->startOfDay())) {
            throw new BaseException(t('validation.calendar.cannot_delete_past'), 422);
        }

        $startTime = $this->parseScheduleTime($schedule->from_time, Carbon::parse($date));
        $endTime = $this->parseScheduleTime($schedule->to_time, Carbon::parse($date));

        if ($this->hasLockedAppointmentsOverlap($doctor->id, $startTime, $endTime)) {
            throw new BaseException(t('validation.calendar.occurrence_has_locked_appointment'), 422);
        }

        $exceptionDates = $schedule->exception_dates ?? [];

        if (!in_array($date, $exceptionDates)) {
            $exceptionDates[] = $date;
            $schedule->update(['exception_dates' => $exceptionDates]);
        }
    }

    /**
     * Silinen occurrence-i restore edir
     */
    public function restoreOccurrence(int $userId, int $scheduleId, string $date): void
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)->findOrFail($scheduleId);

        $date = Carbon::parse($date)->toDateString();

        $exceptionDates = $schedule->exception_dates ?? [];
        $exceptionDates = array_values(array_diff($exceptionDates, [$date]));

        $schedule->update(['exception_dates' => $exceptionDates]);
    }

    /**
     * Unavailability yaradır
     */
    public function createUnavailability(int $userId, array $data)
    {
        $doctorId = $this->doctorIdByUser($userId);

        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);

        if ($start->lt(now())) {
            throw new BaseException(t('validation.calendar.cannot_create_in_past'), 422);
        }

        if ($this->hasLockedAppointmentsOverlap($doctorId, $start, $end)) {
            throw new BaseException(t('validation.calendar.overlaps_locked_appointment'), 422);
        }

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

    /**
     * Unavailability silir
     */
    public function deleteUnavailability(int $userId, int $id): void
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $item = DoctorUnavailability::where('doctor_id', $doctor->id)->findOrFail($id);

        if (Carbon::parse($item->start_time)->lt(now())) {
            throw new BaseException(t('validation.calendar.cannot_delete_past'), 422);
        }

        if ($this->hasLockedAppointmentsOverlap(
            $doctor->id,
            Carbon::parse($item->start_time),
            Carbon::parse($item->end_time)
        )) {
            throw new BaseException(t('validation.calendar.overlaps_locked_appointment'), 422);
        }

        $item->delete();
    }

    /**
     * Appointment statusunu yeniləyir
     */
    public function updateAppointmentStatus(int $userId, int $appointmentId, $status)
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $appointment = Appointment::where('doctor_id', $doctor->id)->findOrFail($appointmentId);

        $appointment->update(['appointment_status' => $status]);

        return $appointment->fresh();
    }

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

    private function assertNoLockedOverlapForRecurring(int $doctorId, array $data): void
    {
        $days = $data['days'] ?? [];
        $period = CarbonPeriod::create(
            Carbon::parse($data['start_date'])->startOfDay(),
            Carbon::parse($data['end_date'] ?? $data['start_date'])->endOfDay()
        );

        foreach ($period as $d) {
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

    private function weekStepMatches(string $startDate, Carbon $candidate, int $every): bool
    {
        $start = Carbon::parse($startDate)->startOfWeek();
        $diff = $start->diffInWeeks($candidate->copy()->startOfWeek());
        return $diff % max($every, 1) === 0;
    }

    private function hasRecurringScheduleOverlap(int $doctorId, int $clinicId, array $data, ?int $excludeId = null): bool
    {
        $q = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('clinic_id', $clinicId);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        $candidates = $q->where(function ($q2) use ($data) {
            $q2->whereBetween('start_date', [$data['start_date'], $data['end_date'] ?? $data['start_date']])
                ->orWhereBetween('end_date', [$data['start_date'], $data['end_date'] ?? $data['start_date']])
                ->orWhere(function ($q3) use ($data) {
                    $q3->where('start_date', '<=', $data['start_date'])
                        ->where('end_date', '>=', $data['end_date'] ?? $data['start_date']);
                });
        })->get();

        foreach ($candidates as $ex) {
            if (!$this->timeRangeOverlaps($data['from_time'], $data['to_time'], $ex->from_time, $ex->to_time)) {
                continue;
            }

            if (($data['frequency'] === 'weekly') || ($ex->frequency === 'weekly')) {
                if (!$this->weekDaysIntersect($data['days'] ?? [], $ex->days ?? [])) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }

    private function timeRangeOverlaps(string $fromA, string $toA, string $fromB, string $toB): bool
    {
        return ($fromA < $toB) && ($toA > $fromB);
    }

    private function weekDaysIntersect(?array $daysA, ?array $daysB): bool
    {
        $a = is_array($daysA) ? $daysA : [];
        $b = is_array($daysB) ? $daysB : [];
        return count(array_intersect($a, $b)) > 0;
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

    private function hasLockedAppointmentsForRecurringDelete(int $doctorId, DoctorSchedule $rec): bool
    {
        $now = Carbon::now()->startOfDay();
        $startDate = Carbon::parse($rec->start_date)->startOfDay();
        $endDate = Carbon::parse($rec->end_date)->endOfDay();

        $windowStart = $now->greaterThan($startDate) ? $now : $startDate;

        $locked = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('clinic_id', $rec->clinic_id)
            ->whereIn('appointment_status', $this->lockedStatuses())
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

        $period = CarbonPeriod::create($windowStart, $endDate);
        $days = is_array($rec->days) ? $rec->days : (json_decode($rec->days ?? '[]', true) ?: []);

        foreach ($period as $d) {
            if ($rec->frequency === 'weekly') {
                if (!in_array((int)$d->dayOfWeek, $days, true)) {
                    continue;
                }
                if (!$this->weekStepMatches($rec->start_date, $d, (int)$rec->every)) {
                    continue;
                }
            }

            $occStart = Carbon::parse($d->toDateString() . ' ' . $rec->from_time);
            $occEnd = Carbon::parse($d->toDateString() . ' ' . $rec->to_time);

            foreach ($locked as $a) {
                if ($occStart->lt($a->end_time) && $occEnd->gt($a->start_time)) {
                    return true;
                }
            }
        }

        return false;
    }
}
