<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorClinic;
use App\Models\DoctorClinicService;
use App\Models\DoctorSchedule;
use App\Models\DoctorUnavailability;
use App\Repositories\Module\DoctorRepository;
use App\Services\BaseCrudService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class DoctorService extends BaseCrudService
{
    public function __construct(DoctorRepository $repository)
    {
        parent::__construct($repository);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD & simple helpers (qısaldılmış, eynilə saxlanılıb)
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Model
    {
        if (isset($data['user_data'])) {
            $userData = $data['user_data'];
            unset($data['user_data']);

            $user = $this->createOrFindUser($userData);
            $data['user_id'] = $user->id;
        }

        if (isset($data['experiences']) && is_array($data['experiences'])) {
            $data['years_of_experience'] = $this->calculateTotalExperience($data['experiences']);
        }

        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Model
    {
        if (isset($data['user_data'])) {
            $doctor = $this->repository->findById($id);
            $this->updateUserData($doctor->user, $data['user_data']);
            unset($data['user_data']);
        }

        if (isset($data['experiences']) && is_array($data['experiences'])) {
            $data['years_of_experience'] = $this->calculateTotalExperience($data['experiences']);
        }

        return $this->repository->update($id, $data);
    }

    public function toggleVerification(int $id): Model
    {
        $doctor = $this->repository->findById($id);
        return $this->repository->update($id, ['is_verified' => !$doctor->is_verified]);
    }

    public function toggleFeatured(int $id): Model
    {
        $doctor = $this->repository->findById($id);
        return $this->repository->update($id, ['is_featured' => !$doctor->is_featured]);
    }

    public function getDoctorsByCategory(int $categoryId, $limit = 0): Collection
    {
        return $this->repository->findByCategory($categoryId, $limit);
    }

    public function getDoctorsRandom(): Collection
    {
        return $this->repository->findRandom();
    }

    public function getDoctorsByClinic(int $clinicId): Collection
    {
        return $this->repository->findByClinic($clinicId);
    }

    public function getVerifiedDoctors(): Collection
    {
        return $this->repository->findVerified();
    }

    public function getPopularDoctors(int $limit = 10): Collection
    {
        return $this->repository->findPopular($limit);
    }

    public function checkDoctorAvailability(int $doctorId, string $date, string $time): bool
    {
        return $this->repository->checkAvailability($doctorId, $date, $time);
    }

    public function addUnavailability(int $doctorId, array $data): void
    {
        $doctor = $this->repository->findById($doctorId);
        $this->assertUnavailabilityCreatable($doctor, $data['start_time'], $data['end_time'], $data['clinic_id'] ?? null);
        $doctor->unavailabilities()->create($data);
    }

    public function updateRating(int $doctorId, float $rating): void
    {
        $doctor = $this->repository->findById($doctorId);
        $totalRatings = $doctor->total_ratings + 1;
        $averageRating = (($doctor->average_rating * $doctor->total_ratings) + $rating) / $totalRatings;

        $this->repository->update($doctorId, [
            'average_rating' => round($averageRating * $totalRatings),
            'total_ratings' => $totalRatings
        ]);
    }

    public function incrementPatientCount(int $doctorId): void
    {
        $doctor = $this->repository->findById($doctorId);
        $this->repository->update($doctorId, ['total_patients' => $doctor->total_patients + 1]);
    }

    public function filters(): array
    {
        return $this->repository->filters();
    }

    /*
    |--------------------------------------------------------------------------
    | Availability – nearest & day range
    |--------------------------------------------------------------------------
    */

    public function getDoctorsWithNearestSlots(array $filters = []): LengthAwarePaginator
    {
        $query = Doctor::with([
            'user',
            'category',
            'subcategory',
            'clinics' => fn($q) => $q->where('doctor_clinic.is_active', true),
            'reviews'
        ]);

        $this->applyFilters($query, $filters);

        $doctors = $query->paginate(10);

        $doctors->getCollection()->transform(function ($doctor) {
            $doctor->nearest_slots = $this->getNearestAvailableSlots($doctor, 3);
            return $doctor;
        });

        return $doctors;
    }

    public function getDoctorWithAvailability($slug)
    {
        $doctor = Doctor::with([
            'user',
            'category',
            'subcategory',
            'clinics', // workingHours yoxdur – çıxardıq
            'educations',
            'experiences',
            'certificates',
            'languages',
            'services',
            'reviews' => fn($q) => $q->with('patient.user')
        ])
            ->whereRelation('user', 'username', $slug)
            ->firstOrFail();

        $doctor->available_days = $this->getAvailableDaysForNextDays($doctor);
        return $doctor;
    }

    /**
     * Həkimin növbəti günlər üçün mövcud günlərini qaytarır
     */
    public function getAvailableDaysForNextDays(Doctor $doctor, int $days = 14): array
    {
        $availableDays = [];
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays($days);

        // DoctorClinic modelindən birbaşa aktiv klinikları al
        $activeClinics = \App\Models\DoctorClinic::where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->with('clinic')
            ->get();

        if ($activeClinics->isEmpty()) {
            return [];
        }

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $daySlots = collect();

            foreach ($activeClinics as $doctorClinic) {
                if (!$doctorClinic->clinic) {
                    continue;
                }

                $slots = $this->getAvailableTimeSlots($doctor, $doctorClinic->clinic->id, $currentDate);

                foreach ($slots as $slot) {
                    $daySlots->push([
                        'time' => $slot['start'],
                        'clinic_id' => $doctorClinic->clinic->id
                    ]);
                }
            }

            if ($daySlots->isNotEmpty()) {
                $availableDays[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_of_week' => $currentDate->format('l'),
                    'day_name' => $this->getAzerbaijaniDayName($currentDate),
                    'display_date' => $this->formatDisplayDate($currentDate),
                    'slots' => $daySlots->sortBy('time')->values()->all()
                ];
            }

            $currentDate->addDay();
        }

        return $availableDays;
    }

    public function getAvailableSlots($doctorId, $clinicId, Carbon $startDate, Carbon $endDate, $serviceId = null): array
    {
        $doctor = Doctor::findOrFail($doctorId);
        $availableSlots = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $daySlots = $this->getAvailableTimeSlots($doctor, $clinicId, $currentDate, $serviceId);

            if (!empty($daySlots)) {
                $availableSlots[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_name' => $this->formatDisplayDate($currentDate),
                    'slots' => $daySlots
                ];
            }
            $currentDate->addDay();
        }

        return [
            'doctor' => $doctor,
            'clinic_id' => $clinicId,
            'service_id' => $serviceId,
            'days' => $availableSlots
        ];
    }

    /**
     * Həkimin yaxın müddətdə mövcud slotlarını tapır
     */
    public function getNearestAvailableSlots(Doctor $doctor, int $limit = 3): \Illuminate\Support\Collection
    {
        $slots = collect();
        $current = Carbon::now();
        $deadline = Carbon::now()->addDays(30);

        // DoctorClinic modelindən birbaşa aktiv klinikları al
        $activeClinics = \App\Models\DoctorClinic::where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->with('clinic')
            ->get();

        if ($activeClinics->isEmpty()) {
            return collect();
        }

        while ($slots->count() < $limit && $current->lte($deadline)) {
            foreach ($activeClinics as $doctorClinic) {
                if (!$doctorClinic->clinic) {
                    continue;
                }

                $daySlots = $this->getAvailableTimeSlots($doctor, $doctorClinic->clinic->id, $current);

                foreach ($daySlots as $slot) {
                    if ($slots->count() >= $limit) break 2;

                    $slots->push([
                        'date' => $current->format('Y-m-d'),
                        'day_name' => $this->getAzerbaijaniDayName($current),
                        'month_name' => $this->getAzerbaijaniMonthName($current),
                        'time' => $slot['start'],
                        'clinic_id' => $doctorClinic->clinic->id,
                        'clinic_name' => $doctorClinic->clinic->name ?? 'Klinika'
                    ]);
                }
            }
            $current->addDay();
        }

        return $slots;
    }

    /*
    |--------------------------------------------------------------------------
    | Core: Günün slotlarını hesabla (RECURRENT SCHEDULE əsasında)
    |--------------------------------------------------------------------------
    */

    /**
     * Günün available time slotlarını hesablayır - sadələşdirilmiş versiya
     */
    public function getAvailableTimeSlots(Doctor $doctor, int $clinicId, Carbon $date, $serviceId = null): array
    {
        $ymd = $date->toDateString();

        // Schedule-ları əldə et
        $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->where('start_date', '<=', $ymd)
            ->where(function ($q) use ($ymd) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $ymd);
            })
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        // Frequency matching-i sadələşdir
        $validSchedules = $schedules->filter(function (DoctorSchedule $schedule) use ($date) {
            return $this->isScheduleValidForDate($schedule, $date);
        });

        if ($validSchedules->isEmpty()) {
            return [];
        }

        // Consultation duration
        $duration = $serviceId
            ? $this->getServiceDuration($doctor, $clinicId, (int)$serviceId)
            : ($doctor->consultation_duration ?: 30);

        // Günün unavailability və appointments
        $unavailabilities = $this->getDayUnavailabilities($doctor, $clinicId, $date);
        $appointments = $this->getDayAppointments($doctor, $clinicId, $date);

        $available = [];

        foreach ($validSchedules as $schedule) {
            $slots = $this->generateSlotsForSchedule($schedule, $date, $duration, $unavailabilities, $appointments);
            $available = array_merge($available, $slots);
        }

        // Sort by time
        usort($available, fn($a, $b) => strcmp($a['start'], $b['start']));

        return $available;
    }

    /**
     * Database-dəki real format-ı nəzərə alaraq time parsing
     */
    private function parseTimeForDate(string $timeString, Carbon $date): ?Carbon
    {
        try {
            // Əgər artıq full datetime format-ındadırsa (2025-09-09 11:00:00)
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timeString)) {
                $datetime = Carbon::parse($timeString);
                // Yalnız time hissəsini götürüb bizim tarix üçün istifadə et
                return $date->copy()->setTime($datetime->hour, $datetime->minute, $datetime->second);
            }

            // Əgər yalnız time format-ındadırsa (11:00:00 və ya 11:00)
            if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $timeString, $matches)) {
                $hour = (int) $matches[1];
                $minute = (int) $matches[2];
                $second = isset($matches[3]) ? (int) $matches[3] : 0;

                if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                    return $date->copy()->setTime($hour, $minute, $second);
                }
            }

            // Son çarə: Carbon-a parse etdirməyə çalış
            $parsed = Carbon::parse($timeString);
            return $date->copy()->setTime($parsed->hour, $parsed->minute, $parsed->second);

        } catch (\Exception $e) {
            \Log::error("Time parsing failed for '{$timeString}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Schedule üçün slotlar generate et
     */
    private function generateSlotsForSchedule(DoctorSchedule $schedule, Carbon $date, int $duration, $unavailabilities, $appointments): array
    {
        $available = [];

        try {
            // Schedule model-dən raw database dəyərlərini al
            $fromTimeRaw = $schedule->getOriginal('from_time') ?? $schedule->from_time;
            $toTimeRaw = $schedule->getOriginal('to_time') ?? $schedule->to_time;

            $startTime = $this->parseTimeForDate($fromTimeRaw, $date);
            $endTime = $this->parseTimeForDate($toTimeRaw, $date);

            if (!$startTime || !$endTime || $endTime->lte($startTime)) {
                return [];
            }

            // İndiki vaxt adjustment (yalnız bugün üçün)
            if ($date->isToday() && Carbon::now()->gt($startTime)) {
                $now = Carbon::now();
                $roundedMinute = ceil($now->minute / $duration) * $duration;

                if ($roundedMinute >= 60) {
                    $startTime = $now->copy()->addHour()->minute(0)->second(0);
                } else {
                    $startTime = $now->copy()->minute($roundedMinute)->second(0);
                }

                if ($startTime->gte($endTime)) {
                    return [];
                }
            }

            // Slot generation
            $cursor = $startTime->copy();
            $maxSlots = 50; // Infinite loop-dan qorunmaq üçün
            $slotCount = 0;

            while ($cursor->copy()->addMinutes($duration)->lte($endTime) && $slotCount < $maxSlots) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($duration);

                if ($this->isSlotAvailable($slotStart, $slotEnd, $unavailabilities, $appointments)) {
                    $available[] = [
                        'start' => $slotStart->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                        'datetime' => $slotStart->format('Y-m-d H:i:s'),
                    ];
                }

                $cursor->addMinutes($duration);
                $slotCount++;
            }

        } catch (\Exception $e) {
            \Log::error("Slot generation error for schedule {$schedule->id}: " . $e->getMessage());
        }

        return $available;
    }

    /**
     * Günün appointment-lərini əldə et
     */
    private function getDayAppointments(Doctor $doctor, int $clinicId, Carbon $date): \Illuminate\Support\Collection
    {
        return Appointment::where('doctor_id', $doctor->id)
            ->where('clinic_id', $clinicId)
            ->whereDate('start_time', $date->toDateString())
            ->whereNotIn('appointment_status', [
                AppointmentStatusEnum::Cancelled,
                AppointmentStatusEnum::NoShow
            ])
            ->get();
    }

    /**
     * Günün unavailability-lərini əldə et
     */
    private function getDayUnavailabilities(Doctor $doctor, int $clinicId, Carbon $date): \Illuminate\Support\Collection
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        return DoctorUnavailability::where('doctor_id', $doctor->id)
            ->where(function ($q) use ($clinicId) {
                $q->where('clinic_id', $clinicId)->orWhereNull('clinic_id');
            })
            ->where(function ($q) use ($dayStart, $dayEnd) {
                $q->whereBetween('start_time', [$dayStart, $dayEnd])
                    ->orWhereBetween('end_time', [$dayStart, $dayEnd])
                    ->orWhere(function ($qq) use ($dayStart, $dayEnd) {
                        $qq->where('start_time', '<=', $dayStart)
                            ->where('end_time', '>=', $dayEnd);
                    });
            })
            ->get();
    }

    /**
     * Schedule-in müəyyən tarixə uyğun olub-olmadığını yoxlayır - sadə versiya
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

    /**
     * Daily schedule validation
     */
    private function isValidDaily(DoctorSchedule $schedule, Carbon $date): bool
    {
        $startDate = Carbon::parse($schedule->start_date);
        $daysDiff = $startDate->diffInDays($date);
        $every = max($schedule->every ?? 1, 1);

        return $daysDiff % $every === 0;
    }

    /**
     * Weekly schedule validation
     */
    private function isValidWeekly(DoctorSchedule $schedule, Carbon $date): bool
    {
        // Əvvəl həftə addımını yoxla
        $startDate = Carbon::parse($schedule->start_date)->startOfWeek();
        $checkDate = $date->copy()->startOfWeek();
        $weeksDiff = $startDate->diffInWeeks($checkDate);
        $every = max($schedule->every ?? 1, 1);

        if ($weeksDiff % $every !== 0) {
            return false;
        }

        // Sonra gün uyğunluğunu yoxla
        $days = is_array($schedule->days) ? $schedule->days : [];

        if (empty($days)) {
            return true; // Əgər gün təyin edilməyibsə, hər gün
        }

        // Carbon-da 0=Sunday, 1=Monday, ... 6=Saturday
        $dayOfWeek = $date->dayOfWeek;

        return in_array($dayOfWeek, $days);
    }

    /**
     * Monthly schedule validation
     */
    private function isValidMonthly(DoctorSchedule $schedule, Carbon $date): bool
    {
        $startDate = Carbon::parse($schedule->start_date);
        $monthsDiff = $startDate->diffInMonths($date);
        $every = max($schedule->every ?? 1, 1);

        if ($monthsDiff % $every !== 0) {
            return false;
        }

        // Eyni gündə olmalıdır
        return $startDate->day === $date->day;
    }

    private function scheduleMatchesDate(DoctorSchedule $sch, Carbon $date): bool
    {
        $freq = $sch->frequency ?? 'weekly';
        $every = max((int)($sch->every ?? 1), 1);

        return match ($freq) {
            'daily' => $this->matchesDaily($sch, $date, $every),
            'weekly' => $this->matchesWeekly($sch, $date, $every),
            'monthly' => $this->matchesMonthly($sch, $date, $every),
            default => false,
        };
    }

    private function matchesDaily(DoctorSchedule $sch, Carbon $date, int $every): bool
    {
        // start_date-dən bu günə qədər neçə gün keçib → every ilə bölünürmü?
        $start = Carbon::parse($sch->start_date)->startOfDay();
        $diff = $start->diffInDays($date->copy()->startOfDay());
        return $diff % $every === 0;
    }

    private function matchesWeekly(DoctorSchedule $sch, Carbon $date, int $every): bool
    {
        if (!$sch->matchesWeekStep($date)) {
            return false;
        }
        return $sch->matchesWeekDay($date);
    }

    private function matchesMonthly(DoctorSchedule $sch, Carbon $date, int $every): bool
    {
        $start = Carbon::parse($sch->start_date)->startOfMonth();
        $diff = ($start->year * 12 + $start->month) - 1;
        $curr = ($date->year * 12 + $date->month) - 1;
        $monthsBetween = abs($curr - $diff);

        if ($monthsBetween % $every !== 0) {
            return false;
        }

        // Ayda konkret gün uyğunluğu (məs: start_date-in günü)
        $targetDay = Carbon::parse($sch->start_date)->day;
        return (int)$date->day === (int)$targetDay;
    }

    private function scheduleWindowForDate(DoctorSchedule $sch, Carbon $date): array
    {
        // from_time/to_time "H:i", onları bu günə yapışdırırıq
        [$h1, $m1] = explode(':', $sch->from_time);
        [$h2, $m2] = explode(':', $sch->to_time);

        $start = $date->copy()->setTime((int)$h1, (int)$m1, 0);
        $end = $date->copy()->setTime((int)$h2, (int)$m2, 0);

        return [$start, $end];
    }

    /*
    |--------------------------------------------------------------------------
    | Schedules & Unavailability create helpers (biznes qaydaları ilə)
    |--------------------------------------------------------------------------
    */

    /**
     * Recurring schedule yaratmaq üçün helper.
     * Biznes qaydası: keçmişə schedule (recurring) başlanğıcı qoyma! (start_date >= today)
     * Eyni pəncərədə eyni klinika üçün üst-üstə düşən aktiv schedule-lar bloklanır.
     */
    public function createDoctorSchedule(Doctor $doctor, array $payload): array
    {
        DB::beginTransaction();
        try {
            $created = [];
            $conflicts = [];

            // həkimin aktiv klinikaları
            $activeClinicIds = $doctor->activeClinics()->pluck('clinics.id')->all();
            if (empty($activeClinicIds)) {
                throw new Exception('Həkimin aktiv klinikası yoxdur');
            }

            foreach ($payload as $item) {
                $clinicId = (int)$item['clinic_id'];

                if (!in_array($clinicId, $activeClinicIds, true)) {
                    $conflicts[] = ['clinic_id' => $clinicId, 'error' => 'Həkim bu klinikada işləmir'];
                    continue;
                }

                // keçmişə recurring yox
                $startDate = Carbon::parse($item['start_date']);
                if ($startDate->isPast()) {
                    $conflicts[] = ['clinic_id' => $clinicId, 'error' => 'Keçmişə schedule yaradıla bilməz'];
                    continue;
                }

                // konflikt check (eyni klinika + kəsişən tarix + kəsişən vaxt aralığı)
                if ($this->hasScheduleConflict($doctor->id, $clinicId, $item)) {
                    $conflicts[] = ['clinic_id' => $clinicId, 'error' => 'Bu aralıqda aktiv schedule mövcuddur'];
                    continue;
                }

                $created[] = DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinicId,
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'] ?? null,
                    'from_time' => $item['from_time'],
                    'to_time' => $item['to_time'],
                    'frequency' => $item['frequency'] ?? 'weekly',
                    'every' => (int)($item['every'] ?? 1),
                    'days' => $item['days'] ?? null,   // weekly üçün [0..6]
                    'is_active' => (bool)($item['is_active'] ?? true),
                    'note' => $item['note'] ?? null,
                ]);
            }

            DB::commit();
            return ['success' => true, 'schedules' => $created, 'conflicts' => $conflicts];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Keçmişə unavailability yaratma, həmçinin confirmed/completed görüşlə üst-üstə düşməsini blokla
     */
    private function assertUnavailabilityCreatable(Doctor $doctor, string $start, string $end, ?int $clinicId = null): void
    {
        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);

        if ($startAt->isPast()) {
            throw new Exception('Keçmiş tarix üçün unavailability yaradıla bilməz');
        }
        if ($endAt->lte($startAt)) {
            throw new Exception('Bitmə vaxtı başlama vaxtından sonra olmalıdır');
        }

        // confirmed/completed randevu ilə kəsişməsin
        $conflictAppt = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->when($clinicId, fn($q) => $q->where('clinic_id', $clinicId))
            ->whereIn('appointment_status', [AppointmentStatusEnum::Confirmed, AppointmentStatusEnum::Completed])
            ->where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('start_time', [$startAt, $endAt])
                    ->orWhereBetween('end_time', [$startAt, $endAt])
                    ->orWhere(function ($qq) use ($startAt, $endAt) {
                        $qq->where('start_time', '<', $startAt)->where('end_time', '>', $endAt);
                    });
            })
            ->exists();

        if ($conflictAppt) {
            throw new Exception('Təsdiqlənmiş və ya tamamlanmış randevu ilə üst-üstə düşür');
        }
    }

    private function hasScheduleConflict(int $doctorId, int $clinicId, array $item): bool
    {
        $startDate = Carbon::parse($item['start_date'])->toDateString();
        $endDate = isset($item['end_date']) ? Carbon::parse($item['end_date'])->toDateString() : null;

        $from = $item['from_time']; // "H:i"
        $to = $item['to_time'];

        $existing = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('clinic_id', $clinicId)
            ->active()
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate ?? $startDate])
                    ->orWhere(function ($qq) use ($startDate, $endDate) {
                        $qq->where('start_date', '<=', $startDate)
                            ->where(function ($qqq) use ($endDate, $startDate) {
                                if ($endDate) {
                                    $qqq->whereNull('end_date')->orWhere('end_date', '>=', $endDate);
                                } else {
                                    $qqq->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                                }
                            });
                    });
            })
            ->get();

        foreach ($existing as $sch) {
            // vaxt aralığı kəsişirmi?
            if ($this->timeRangesOverlap($from, $to, $sch->from_time, $sch->to_time)) {
                return true;
            }
        }

        return false;
    }

    private function timeRangesOverlap(string $from1, string $to1, string $from2, string $to2): bool
    {
        [$h1s, $m1s] = explode(':', $from1);
        [$h1e, $m1e] = explode(':', $to1);
        [$h2s, $m2s] = explode(':', $from2);
        [$h2e, $m2e] = explode(':', $to2);

        $s1 = (int)$h1s * 60 + (int)$m1s;
        $e1 = (int)$h1e * 60 + (int)$m1e;
        $s2 = (int)$h2s * 60 + (int)$m2s;
        $e2 = (int)$h2e * 60 + (int)$m2e;

        return ($s1 < $e2) && ($e1 > $s2);
    }

    /*
    |--------------------------------------------------------------------------
    | Slot availability checks
    |--------------------------------------------------------------------------
    */

    /**
     * Slot-un available olub-olmadığını yoxla - dəyişdirmədim
     */
    private function isSlotAvailable(Carbon $slotStart, Carbon $slotEnd, $unavailabilities, $appointments): bool
    {
        // Unavailability yoxla
        foreach ($unavailabilities as $unavailability) {
            if ($this->timesOverlap($slotStart, $slotEnd, $unavailability->start_time, $unavailability->end_time)) {
                return false;
            }
        }

        // Appointment yoxla
        foreach ($appointments as $appointment) {
            if ($this->timesOverlap($slotStart, $slotEnd, $appointment->start_time, $appointment->end_time)) {
                return false;
            }
        }

        return true;
    }

    /**
     * İki zaman aralığının üst-üstə düşüb-düşmədiyini yoxla - dəyişdirmədim
     */
    private function timesOverlap($start1, $end1, $start2, $end2): bool
    {
        $s1 = $start1 instanceof Carbon ? $start1 : Carbon::parse($start1);
        $e1 = $end1 instanceof Carbon ? $end1 : Carbon::parse($end1);
        $s2 = $start2 instanceof Carbon ? $start2 : Carbon::parse($start2);
        $e2 = $end2 instanceof Carbon ? $end2 : Carbon::parse($end2);

        return ($s1->lt($e2)) && ($e1->gt($s2));
    }

    /*
    |--------------------------------------------------------------------------
    | Service duration – doctor_clinic → doctor_clinic_services
    |--------------------------------------------------------------------------
    */

    private function getDoctorClinicId(int $doctorId, int $clinicId): ?int
    {
        return DoctorClinic::query()
            ->where('doctor_id', $doctorId)
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->value('id');
    }

    /**
     * Service duration əldə et
     */
    private function getServiceDuration(Doctor $doctor, int $clinicId, int $serviceId): int
    {
        $doctorClinic = \App\Models\DoctorClinic::where('doctor_id', $doctor->id)
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->first();

        if ($doctorClinic) {
            $service = \App\Models\DoctorClinicService::where('doctor_clinic_id', $doctorClinic->id)
                ->where('service_id', $serviceId)
                ->where('is_active', true)
                ->first();

            if ($service && $service->duration) {
                return (int)$service->duration;
            }
        }

        return (int)($doctor->consultation_duration ?: 30);
    }

    /*
    |--------------------------------------------------------------------------
    | Misc: format & filters
    |--------------------------------------------------------------------------
    */

    private function formatDisplayDate(Carbon $date): string
    {
        if ($date->isToday()) {
            return "Today, " . $this->getAzerbaijaniMonthName($date) . " " . $date->day;
        } elseif ($date->isTomorrow()) {
            return "Tomorrow, " . $this->getAzerbaijaniMonthName($date) . " " . $date->day;
        }
        return $this->getAzerbaijaniDayName($date) . ", " . $this->getAzerbaijaniMonthName($date) . " " . $date->day;
    }

    private function getAzerbaijaniDayName(Carbon $date): string
    {
        $days = [
            'Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed',
            'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'
        ];
        return $days[$date->format('l')] ?? $date->format('D');
    }

    private function getAzerbaijaniMonthName(Carbon $date): string
    {
        $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        return $months[$date->month] ?? $date->format('M');
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['category_id'])) {
            $query->where('category', $filters['category_id']);
        }

        if (!empty($filters['clinic_id'])) {
            $query->whereHas('clinics', function ($q) use ($filters) {
                $q->where('clinic_id', $filters['clinic_id'])
                    ->where('doctor_clinic.is_active', true);
            });
        }

        if (!empty($filters['search'])) {
            $query->searchByName($filters['search']);
        }

        if (!empty($filters['is_verified'])) {
            $query->verified();
        }

        if (!empty($filters['gender'])) {
            $query->whereHas('user', fn($q) => $q->where('gender', $filters['gender']));
        }

        if (!empty($filters['language'])) {
            $query->whereHas('languages', fn($q) => $q->where('language', $filters['language']));
        }

        if (!empty($filters['available_for_home_visit'])) {
            $query->homeVisit();
        }

        if (!empty($filters['available_for_online_consultation'])) {
            $query->onlineConsultation();
        }

        if (!empty($filters['price_min'])) {
            $query->where('consultation_fee', '>=', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $query->where('consultation_fee', '<=', $filters['price_max']);
        }

        $query->orderByRating();
    }

    /*
    |--------------------------------------------------------------------------
    | Private helpers
    |--------------------------------------------------------------------------
    */

    private function createOrFindUser(array $userData): \App\Models\User
    {
        if (!empty($userData['email'])) {
            if ($u = \App\Models\User::where('email', $userData['email'])->first()) {
                return $u;
            }
        }
        return \App\Models\User::create($userData);
    }

    private function updateUserData(\App\Models\User $user, array $userData): void
    {
        $user->update($userData);
    }

    private function calculateTotalExperience(array $experiences): int
    {
        $totalYears = 0;
        foreach ($experiences as $exp) {
            $start = Carbon::parse($exp['start_date']);
            $end = !empty($exp['end_date']) ? Carbon::parse($exp['end_date']) : now();
            $totalYears += $start->diffInYears($end);
        }
        return $totalYears;
    }
}
