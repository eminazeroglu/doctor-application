<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\Doctor;
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

    /**
     * Həkim yaradır
     */
    public function create(array $data): Model
    {
        // İstifadəçi məlumatlarını ayrı şəkildə handle edirik
        if (isset($data['user_data'])) {
            $userData = $data['user_data'];
            unset($data['user_data']);

            // İstifadəçini yaradırıq və ya tapırıq
            $user = $this->createOrFindUser($userData);
            $data['user_id'] = $user->id;
        }

        // İş təcrübəsini hesablayırıq
        if (isset($data['experiences']) && is_array($data['experiences'])) {
            $data['years_of_experience'] = $this->calculateTotalExperience($data['experiences']);
        }

        return $this->repository->create($data);
    }

    /**
     * Həkim məlumatlarını yeniləyir
     */
    public function update(int $id, array $data): Model
    {
        // İstifadəçi məlumatlarını yeniləyirik
        if (isset($data['user_data'])) {
            $doctor = $this->repository->findById($id);
            $this->updateUserData($doctor->user, $data['user_data']);
            unset($data['user_data']);
        }

        // İş təcrübəsini yenidən hesablayırıq
        if (isset($data['experiences']) && is_array($data['experiences'])) {
            $data['years_of_experience'] = $this->calculateTotalExperience($data['experiences']);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Həkimin təsdiq statusunu dəyişir
     */
    public function toggleVerification(int $id): Model
    {
        $doctor = $this->repository->findById($id);
        return $this->repository->update($id, [
            'is_verified' => !$doctor->is_verified
        ]);
    }

    /**
     * Həkimin populyar statusunu dəyişir
     */
    public function toggleFeatured(int $id): Model
    {
        $doctor = $this->repository->findById($id);
        return $this->repository->update($id, [
            'is_featured' => !$doctor->is_featured
        ]);
    }

    /**
     * İxtisasa görə həkimləri gətirir
     */
    public function getDoctorsByCategory(int $categoryId, $limit = 0): Collection
    {
        return $this->repository->findByCategory($categoryId, $limit);
    }

    /**
     * Random həkimləri gətirir
     */
    public function getDoctorsRandom(): Collection
    {
        return $this->repository->findRandom();
    }

    /**
     * Klinikaya görə həkimləri gətirir
     */
    public function getDoctorsByClinic(int $clinicId): Collection
    {
        return $this->repository->findByClinic($clinicId);
    }

    /**
     * Təsdiqlənmiş həkimləri gətirir
     */
    public function getVerifiedDoctors(): Collection
    {
        return $this->repository->findVerified();
    }

    /**
     * Populyar həkimləri gətirir
     */
    public function getPopularDoctors(int $limit = 10): Collection
    {
        return $this->repository->findPopular($limit);
    }

    /**
     * Həkimin mövcudluğunu yoxlayır
     */
    public function checkDoctorAvailability(int $doctorId, string $date, string $time): bool
    {
        return $this->repository->checkAvailability($doctorId, $date, $time);
    }

    /**
     * Həkim məşğulluğu əlavə edir
     */
    public function addUnavailability(int $doctorId, array $data): void
    {
        $doctor = $this->repository->findById($doctorId);
        $doctor->unavailabilities()->create($data);
    }

    /**
     * Həkimin qiymətləndirməsini yeniləyir
     */
    public function updateRating(int $doctorId, float $rating): void
    {
        $doctor = $this->repository->findById($doctorId);

        $totalRatings = $doctor->total_ratings + 1;
        $averageRating = (($doctor->average_rating * $doctor->total_ratings) + $rating) / $totalRatings;

        $this->repository->update($doctorId, [
            'average_rating' => round($averageRating * $totalRatings), // Ümumi rating
            'total_ratings' => $totalRatings
        ]);
    }

    /**
     * Həkimin xəstə sayını artırır
     */
    public function incrementPatientCount(int $doctorId): void
    {
        $doctor = $this->repository->findById($doctorId);

        $this->repository->update($doctorId, [
            'total_patients' => $doctor->total_patients + 1
        ]);
    }

    /**
     * Filtrlər üçün məlumatları gətirir
     */
    public function filters(): array
    {
        return $this->repository->filters();
    }

    /**
     * Həkimləri yaxın randevu vaxtları ilə birlikdə qaytarır
     */
    public function getDoctorsWithNearestSlots(array $filters = []): LengthAwarePaginator
    {
        $query = Doctor::with([
            'user',
            'category',
            'subcategory',
            'clinics' => function($q) {
                $q->where('doctor_clinic.is_active', true);
            },
            'reviews'
        ]);

        // Filterləri tətbiq et
        $this->applyFilters($query, $filters);

        $doctors = $query->paginate(10);

        // Hər həkim üçün yaxın 3 randevu vaxtını tap
        $doctors->getCollection()->transform(function ($doctor) {
            $doctor->nearest_slots = $this->getNearestAvailableSlots($doctor, 3);
            return $doctor;
        });

        return $doctors;
    }

    /**
     * Həkimin detallı məlumatı ilə mövcud vaxtları
     */
    public function getDoctorWithAvailability($id)
    {
        $doctor = Doctor::with([
            'user',
            'category',
            'subcategory',
            'clinics' => function($q) {
                $q->with('workingHours');
            },
            'educations',
            'experiences',
            'certificates',
            'languages',
            'services',
            'reviews' => function($q) {
                $q->with('patient.user');
            }
        ])->findOrFail($id);

        // Növbəti 14 gün üçün mövcud vaxtları tap
        $doctor->available_days = $this->getAvailableDaysForNextDays($doctor);

        return $doctor;
    }

    /**
     * Növbəti 14 gün üçün mövcud günləri və vaxtları qaytarır
     */
    public function getAvailableDaysForNextDays(Doctor $doctor): array
    {
        $availableDays = [];
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(14);

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $daySlots = collect();

            foreach ($doctor->activeClinics as $clinic) {
                $slots = $this->getAvailableTimeSlots($doctor, $clinic->id, $currentDate);

                foreach ($slots as $slot) {
                    $daySlots->push([
                        'time' => $slot['start'],
                        'clinic_id' => $clinic->id
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

    /**
     * Müəyyən tarix aralığı üçün mövcud slotları qaytarır
     */
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
     * Həkim üçün iş qrafiki yaradır
     */
    public function createDoctorSchedule(Doctor $doctor, array $scheduleData): array
    {
        try {
            DB::beginTransaction();

            $createdSchedules = [];
            $conflicts = [];

            // Həkimin aktiv klinikalarını əldə edirik
            $activeClinics = $doctor->activeClinics()->get();

            if ($activeClinics->isEmpty()) {
                throw new Exception('Həkimin aktiv klinikası yoxdur');
            }

            foreach ($scheduleData as $clinicSchedule) {
                $clinicId = $clinicSchedule['clinic_id'];

                // Həkimin bu klinikada işləyib-işləmədiyini yoxlayırıq
                if (!$activeClinics->contains('id', $clinicId)) {
                    $conflicts[] = [
                        'clinic_id' => $clinicId,
                        'error' => 'Həkim bu klinikada işləmir'
                    ];
                    continue;
                }

                // Həftənin günləri üçün schedule yaradırıq
                foreach ($clinicSchedule['days'] as $daySchedule) {
                    // Mövcud schedule-u yoxlayırıq
                    $existingSchedule = DoctorSchedule::where([
                        'doctor_id' => $doctor->id,
                        'clinic_id' => $clinicId,
                        'day_of_week' => $daySchedule['day_of_week']
                    ])->first();

                    if ($existingSchedule) {
                        // Mövcud schedule varsa, yeniləyirik
                        $schedule = $this->updateExistingSchedule($existingSchedule, $daySchedule);
                    } else {
                        // Yeni schedule yaradırıq
                        $schedule = $this->createNewSchedule($doctor->id, $clinicId, $daySchedule);
                    }

                    // Vaxt konfliktlərini yoxlayırıq
                    $hasConflict = $this->checkScheduleConflicts(
                        $doctor,
                        $clinicId,
                        $daySchedule['day_of_week'],
                        $daySchedule['start_time'],
                        $daySchedule['end_time'],
                        $schedule->id ?? null
                    );

                    if ($hasConflict) {
                        $conflicts[] = [
                            'clinic_id' => $clinicId,
                            'day' => $daySchedule['day_of_week'],
                            'error' => 'Bu vaxt aralığında konflikt var'
                        ];
                        continue;
                    }

                    $createdSchedules[] = $schedule;
                }
            }

            // Məşğulluq vaxtlarını əlavə edirik
            if (isset($scheduleData['unavailabilities'])) {
                $this->createUnavailabilities($doctor, $scheduleData['unavailabilities']);
            }

            DB::commit();

            return [
                'success' => true,
                'schedules' => $createdSchedules,
                'conflicts' => $conflicts,
                'message' => count($createdSchedules) . ' iş qrafiki uğurla yaradıldı'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('İş qrafiki yaradılarkən xəta baş verdi: ' . $e->getMessage());
        }
    }

    /**
     * Həkimin yaxın mövcud vaxt slotlarını tapır
     */
    public function getNearestAvailableSlots(Doctor $doctor, int $limit = 3): \Illuminate\Support\Collection
    {
        $slots = collect();
        $currentDate = Carbon::now();
        $endDate = Carbon::now()->addDays(30);

        while ($slots->count() < $limit && $currentDate->lte($endDate)) {
            foreach ($doctor->activeClinics as $clinic) {
                $daySlots = $this->getAvailableTimeSlots($doctor, $clinic->id, $currentDate);

                foreach ($daySlots as $slot) {
                    if ($slots->count() >= $limit) break 2;

                    $slots->push([
                        'date' => $currentDate->format('Y-m-d'),
                        'day_name' => $this->getAzerbaijaniDayName($currentDate),
                        'month_name' => $this->getAzerbaijaniMonthName($currentDate),
                        'time' => $slot['start'],
                        'clinic_id' => $clinic->id,
                        'clinic_name' => $clinic->name
                    ]);
                }
            }

            $currentDate->addDay();
        }

        return $slots;
    }

    /**
     * Həkimin müəyyən tarix üçün boş vaxtlarını qaytarır
     */
    public function getAvailableTimeSlots(Doctor $doctor, int $clinicId, Carbon $date, $serviceId = null): array
    {
        $dayOfWeek = $date->format('l'); // Monday, Tuesday, etc.

        // Həmin gün üçün iş qrafikini tapırıq
        $schedule = DoctorSchedule::where([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'day_of_week' => $dayOfWeek,
            'is_active' => true
        ])->first();

        if (!$schedule) {
            return [];
        }

        // Həmin gün üçün məşğulluqları tapırıq
        $unavailabilities = DoctorUnavailability::where('doctor_id', $doctor->id)
            ->where(function($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId)
                    ->orWhereNull('clinic_id'); // Bütün klinikalar üçün məşğulluq
            })
            ->where(function($query) use ($date) {
                $startOfDay = $date->copy()->startOfDay();
                $endOfDay = $date->copy()->endOfDay();

                $query->whereBetween('start_datetime', [$startOfDay, $endOfDay])
                    ->orWhereBetween('end_datetime', [$startOfDay, $endOfDay])
                    ->orWhere(function($q) use ($startOfDay, $endOfDay) {
                        $q->where('start_datetime', '<=', $startOfDay)
                            ->where('end_datetime', '>=', $endOfDay);
                    });
            })
            ->get();

        // Həmin gün üçün mövcud randevuları tapırıq
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->where('clinic_id', $clinicId)
            ->whereDate('start_time', $date->format('Y-m-d'))
            ->whereNotIn('appointment_status', [
                AppointmentStatusEnum::Cancelled,
                AppointmentStatusEnum::NoShow
            ])
            ->get();

        // Xidmət müddətini təyin edirik
        $duration = $serviceId ?
            $this->getServiceDuration($doctor, $clinicId, $serviceId) :
            $schedule->appointment_duration;

        // Boş vaxt slotlarını hesablayırıq
        $availableSlots = [];
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);

        $currentSlot = $startTime->copy();

        // Əgər bugündürsə və indiki vaxt iş saatlarından sonradırsa, boş slot yoxdur
        if ($date->isToday() && Carbon::now()->gte($endTime)) {
            return [];
        }

        // Əgər bugündürsə, indiki vaxtdan başla
        if ($date->isToday() && Carbon::now()->gt($startTime)) {
            // İndiki vaxtı yaxın slot vaxtına yuvarlaqlaşdır
            $now = Carbon::now();
            $minutes = $now->minute;
            $roundedMinutes = ceil($minutes / $duration) * $duration;
            $currentSlot = $now->copy()->minute($roundedMinutes)->second(0);
        }

        while ($currentSlot->copy()->addMinutes($duration)->lte($endTime)) {
            $slotEnd = $currentSlot->copy()->addMinutes($duration);

            // Bu slot məşğul və ya randevuludursa, keçirik
            if (!$this->isSlotAvailable($currentSlot, $slotEnd, $unavailabilities, $appointments)) {
                $currentSlot->addMinutes($duration);
                continue;
            }

            $availableSlots[] = [
                'start' => $currentSlot->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'datetime' => $currentSlot->format('Y-m-d H:i:s')
            ];

            $currentSlot->addMinutes($duration);
        }

        return $availableSlots;
    }

    /**
     * İstifadəçini yaradır və ya tapır
     */
    private function createOrFindUser(array $userData): \App\Models\User
    {
        if (isset($userData['email'])) {
            $user = \App\Models\User::where('email', $userData['email'])->first();
            if ($user) {
                return $user;
            }
        }

        return \App\Models\User::create($userData);
    }

    /**
     * İstifadəçi məlumatlarını yeniləyir
     */
    private function updateUserData(\App\Models\User $user, array $userData): void
    {
        $user->update($userData);
    }

    /**
     * Ümumi iş təcrübəsini hesablayır
     */
    private function calculateTotalExperience(array $experiences): int
    {
        $totalYears = 0;

        foreach ($experiences as $experience) {
            $startDate = Carbon::parse($experience['start_date']);
            $endDate = isset($experience['end_date'])
                ? Carbon::parse($experience['end_date'])
                : now();

            $totalYears += $startDate->diffInYears($endDate);
        }

        return $totalYears;
    }

    /**
     * Vaxt slotunun mövcud olub-olmadığını yoxlayır
     */
    private function isSlotAvailable($slotStart, $slotEnd, $unavailabilities, $appointments): bool
    {
        // Məşğulluqları yoxlayırıq
        foreach ($unavailabilities as $unavailability) {
            if ($this->timesOverlap(
                $slotStart,
                $slotEnd,
                $unavailability->start_datetime,
                $unavailability->end_datetime
            )) {
                return false;
            }
        }

        // Randevuları yoxlayırıq
        foreach ($appointments as $appointment) {
            if ($this->timesOverlap(
                $slotStart,
                $slotEnd,
                $appointment->start_time,
                $appointment->end_time
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * İki vaxt aralığının kəsişib-kəsişmədiyini yoxlayır
     */
    private function timesOverlap($start1, $end1, $start2, $end2): bool
    {
        // Carbon obyektlərinə çeviririk
        if (!($start1 instanceof Carbon)) {
            $start1 = Carbon::parse($start1);
        }
        if (!($end1 instanceof Carbon)) {
            $end1 = Carbon::parse($end1);
        }
        if (!($start2 instanceof Carbon)) {
            $start2 = Carbon::parse($start2);
        }
        if (!($end2 instanceof Carbon)) {
            $end2 = Carbon::parse($end2);
        }

        return ($start1->lt($end2)) && ($end1->gt($start2));
    }

    /**
     * Yeni schedule yaradır
     */
    private function createNewSchedule(int $doctorId, int $clinicId, array $daySchedule): DoctorSchedule
    {
        return DoctorSchedule::create([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'day_of_week' => $daySchedule['day_of_week'],
            'start_time' => $daySchedule['start_time'],
            'end_time' => $daySchedule['end_time'],
            'is_active' => $daySchedule['is_active'] ?? true,
            'max_appointments' => $daySchedule['max_appointments'] ?? null,
            'appointment_duration' => $daySchedule['appointment_duration'] ?? 30,
            'note' => $daySchedule['note'] ?? null
        ]);
    }

    /**
     * Mövcud schedule-u yeniləyir
     */
    private function updateExistingSchedule(DoctorSchedule $schedule, array $daySchedule): DoctorSchedule
    {
        $schedule->update([
            'start_time' => $daySchedule['start_time'],
            'end_time' => $daySchedule['end_time'],
            'is_active' => $daySchedule['is_active'] ?? $schedule->is_active,
            'max_appointments' => $daySchedule['max_appointments'] ?? $schedule->max_appointments,
            'appointment_duration' => $daySchedule['appointment_duration'] ?? $schedule->appointment_duration,
            'note' => $daySchedule['note'] ?? $schedule->note
        ]);

        return $schedule;
    }

    /**
     * Schedule konfliktlərini yoxlayır
     */
    private function checkScheduleConflicts(
        Doctor $doctor,
        int $clinicId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeScheduleId = null
    ): bool {
        $query = DoctorSchedule::where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        $otherSchedules = $query->get();

        foreach ($otherSchedules as $otherSchedule) {
            $newStart = Carbon::parse($startTime);
            $newEnd = Carbon::parse($endTime);
            $existingStart = Carbon::parse($otherSchedule->start_time);
            $existingEnd = Carbon::parse($otherSchedule->end_time);

            if (
                ($newStart->between($existingStart, $existingEnd)) ||
                ($newEnd->between($existingStart, $existingEnd)) ||
                ($existingStart->between($newStart, $newEnd)) ||
                ($existingEnd->between($newStart, $newEnd))
            ) {
                if ($otherSchedule->clinic_id == $clinicId) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Həkim məşğulluqlarını yaradır
     */
    private function createUnavailabilities(Doctor $doctor, array $unavailabilities): void
    {
        foreach ($unavailabilities as $unavailability) {
            $startDateTime = Carbon::parse($unavailability['start_datetime']);
            $endDateTime = Carbon::parse($unavailability['end_datetime']);

            if ($unavailability['is_recurring'] ?? false) {
                $this->createRecurringUnavailability($doctor, $unavailability);
            } else {
                DoctorUnavailability::create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $unavailability['clinic_id'] ?? null,
                    'start_datetime' => $startDateTime,
                    'end_datetime' => $endDateTime,
                    'reason' => $unavailability['reason'] ?? null,
                    'description' => $unavailability['description'] ?? null,
                    'is_recurring' => false
                ]);
            }
        }
    }

    /**
     * Təkrarlanan məşğulluqları yaradır
     */
    private function createRecurringUnavailability(Doctor $doctor, array $unavailability): void
    {
        $pattern = $unavailability['recurring_pattern'];
        $startDate = Carbon::parse($unavailability['start_datetime']);
        $endDate = Carbon::parse($unavailability['end_datetime']);
        $recurringEndDate = Carbon::parse($unavailability['recurring_end_date'] ?? now()->addMonths(3));

        $currentStart = $startDate->copy();
        $currentEnd = $endDate->copy();

        while ($currentStart->lte($recurringEndDate)) {
            DoctorUnavailability::create([
                'doctor_id' => $doctor->id,
                'clinic_id' => $unavailability['clinic_id'] ?? null,
                'start_datetime' => $currentStart->copy(),
                'end_datetime' => $currentEnd->copy(),
                'reason' => $unavailability['reason'] ?? null,
                'description' => $unavailability['description'] ?? null,
                'is_recurring' => true,
                'recurring_pattern' => $pattern
            ]);

            switch ($pattern) {
                case 'daily':
                    $currentStart->addDay();
                    $currentEnd->addDay();
                    break;
                case 'weekly':
                    $currentStart->addWeek();
                    $currentEnd->addWeek();
                    break;
                case 'monthly':
                    $currentStart->addMonth();
                    $currentEnd->addMonth();
                    break;
            }
        }
    }

    /**
     * Xidmətin müddətini qaytarır
     */
    private function getServiceDuration(Doctor $doctor, int $clinicId, int $serviceId): int
    {
        $service = $doctor->services()
            ->wherePivot('clinic_id', $clinicId)
            ->wherePivot('service_id', $serviceId)
            ->first();

        return $service ? $service->pivot->duration : 30;
    }

    /**
     * Tarixi format edir
     */
    private function formatDisplayDate(Carbon $date): string
    {
        if ($date->isToday()) {
            return "Today, " . $this->getAzerbaijaniMonthName($date) . " " . $date->day;
        } elseif ($date->isTomorrow()) {
            return "Tomorrow, " . $this->getAzerbaijaniMonthName($date) . " " . $date->day;
        } else {
            return $this->getAzerbaijaniDayName($date) . ", " .
                $this->getAzerbaijaniMonthName($date) . " " . $date->day;
        }
    }

    /**
     * Azərbaycan dilində gün adı
     */
    private function getAzerbaijaniDayName(Carbon $date): string
    {
        $days = [
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun'
        ];

        return $days[$date->format('l')] ?? $date->format('D');
    }

    /**
     * Azərbaycan dilində ay adı
     */
    private function getAzerbaijaniMonthName(Carbon $date): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];

        return $months[$date->month] ?? $date->format('M');
    }

    /**
     * Filterləri tətbiq et
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['category_id'])) {
            $query->where('category', $filters['category_id']);
        }

        if (!empty($filters['clinic_id'])) {
            $query->whereHas('clinics', function($q) use ($filters) {
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
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        if (!empty($filters['language'])) {
            $query->whereHas('languages', function($q) use ($filters) {
                $q->where('language', $filters['language']);
            });
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
}
