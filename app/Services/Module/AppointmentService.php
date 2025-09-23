<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Exceptions\BaseException;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\DoctorUnavailability;
use App\Repositories\Module\AppointmentRepository;
use App\Services\BaseCrudService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class AppointmentService extends BaseCrudService
{
    public function __construct(AppointmentRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Yeni randevu yaradır və konflikləri yoxlayır
     * @throws Throwable
     */
    public function create(array $data): Model
    {
        // Məlumatları valide et
        $this->validateAppointmentData($data);

        // Zaman konfliktini yoxla
        $this->checkTimeConflict($data);

        // Həkimin mövcudluğunu yoxla
        $this->checkDoctorAvailability($data);

        if (!auth()->user()->hasPatient()) {
            throw new BaseException('Sizin profiliniz pasient profili deyil');
        }

        $data['patient_id'] = auth()->user()->patient->id;

        $data['appointment_status'] = AppointmentStatusEnum::Pending;


        DB::beginTransaction();
        try {
            // Randevu yarat
            $appointment = $this->repository->create($data);

            // Əlavə əməliyyatlar
            $this->handlePostCreation($appointment);

            DB::commit();
            return $appointment;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Randevu yenilə
     * @throws Throwable
     */
    public function update(int $id, array $data): Model
    {
        $appointment = $this->repository->findById($id);

        // Əgər vaxt dəyişdirilirsə, konflikt yoxla
        if (isset($data['start_time']) || isset($data['end_time'])) {
            $this->checkTimeConflict($data, $id);
            $this->checkDoctorAvailability($data, $id);
        }

        DB::beginTransaction();
        try {
            $updatedAppointment = $this->repository->update($id, $data);

            // Status dəyişiklikləri idarə et
            if (isset($data['appointment_status'])) {
                $this->handleStatusChange($updatedAppointment, $appointment->appointment_status);
            }

            DB::commit();
            return $updatedAppointment;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Randevu statusunu dəyişdir
     * @throws Throwable
     */
    public function customChangeStatus(int $id, array $data): Model
    {
        $appointment = $this->repository->findById($id);

        $statusField = $data['action'] ?? 'appointment_status';
        $status = $data['status'] ?? $data['value'];
        $reason = $data['reason'] ?? null;

        DB::beginTransaction();
        try {
            $updatedAppointment = $this->repository->updateStatus($id, $status, $reason);
            $this->handleStatusChange($updatedAppointment, $appointment->appointment_status);

            DB::commit();
            return $updatedAppointment;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Randevu məlumatlarını valide et
     * @throws BaseException
     */
    private function validateAppointmentData(array $data): void
    {
        // Keçmiş tarixə randevu yaradılmasını əngəllə
        if (isset($data['start_time']) && Carbon::parse($data['start_time'])->isPast()) {
            throw new BaseException([
                'start_time' => 'Keçmiş tarixə randevu təyin edilə bilməz'
            ], 422);
        }

        // Başlama vaxtı bitiş vaxtından kiçik olmalıdır
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);

            if ($startTime->gte($endTime)) {
                throw new BaseException([
                    'end_time' => 'Bitiş vaxtı başlama vaxtından böyük olmalıdır'
                ], 422);
            }
        }
    }

    /**
     * Zaman konfliktini yoxla
     * @throws BaseException
     */
    private function checkTimeConflict(array $data, int $excludeId = null): void
    {
        if (!isset($data['doctor_id']) || !isset($data['start_time']) || !isset($data['end_time'])) {
            return;
        }

        $hasConflict = $this->repository->checkConflict(
            $data['doctor_id'],
            $data['start_time'],
            $data['end_time'],
            $excludeId
        );

        if ($hasConflict) {
            throw new BaseException([
                'start_time' => 'Bu vaxt aralığında həkimin başqa randevusu var'
            ], 422);
        }
    }

    /**
     * Həkimin mövcudluğunu yoxla
     * @throws BaseException
     */
    private function checkDoctorAvailability(array $data, int $excludeId = null): void
    {
        if (!isset($data['doctor_id']) || !isset($data['start_time'])) {
            return;
        }

        // Həkimin iş saatlarını yoxla
        $startTime = Carbon::parse($data['start_time']);
        $dayOfWeek = (int)$startTime->format('N');


        $schedule = DoctorSchedule::query()
            ->where('doctor_id', $data['doctor_id'])
            ->whereJsonContains('days', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            throw new BaseException([
                'start_time' => 'Həkimin bu gün iş saatı yoxdur'
            ], 422);
        }

        // Həkimin məşğulluğunu yoxla
        $unavailability = DoctorUnavailability::query()
            ->where('doctor_id', $data['doctor_id'])
            ->where('start_time', '<=', $data['start_time'])
            ->where('end_time', '>=', $data['start_time'])
            ->exists();

        if ($unavailability) {
            throw new BaseException([
                'start_time' => 'Həkim bu vaxtda məşğuldur'
            ], 422);
        }
    }

    /**
     * Randevu yaradıldıqdan sonrakı əməliyyatlar
     */
    private function handlePostCreation($appointment): void
    {
        // Xatırlatmalar yarat
        $this->createReminders($appointment);

        // Bildirişlər göndər
        $this->sendNotifications($appointment);
    }

    /**
     * Status dəyişikliklərini idarə et
     */
    private function handleStatusChange($appointment, $oldStatus): void
    {
        $newStatus = $appointment->appointment_status;

        if ($oldStatus === $newStatus) {
            return;
        }

        // Status əsaslı əməliyyatlar
        switch ($newStatus) {
            case AppointmentStatusEnum::Confirmed:
                $this->sendNotifications($appointment, 'confirmed');
                break;

            case AppointmentStatusEnum::Cancelled:
                $this->sendNotifications($appointment, 'cancelled');
                $this->handleCancellation($appointment);
                break;

            case AppointmentStatusEnum::Completed:
                $this->sendNotifications($appointment, 'completed');
                $this->handleCompletion($appointment);
                break;

            case AppointmentStatusEnum::Rescheduled:
                $this->sendNotifications($appointment, 'rescheduled');
                break;
        }
    }

    /**
     * Xatırlatmalar yarat
     */
    private function createReminders($appointment): void
    {
        $startTime = Carbon::parse($appointment->start_time);

        // 24 saat əvvəl e-poçt
        if ($startTime->subDay()->isFuture()) {
            $appointment->reminders()->create([
                'type' => 'email',
                'send_at' => $startTime->subDay(),
                'is_sent' => false
            ]);
        }

        // 1 saat əvvəl SMS
        if ($startTime->subHour()->isFuture()) {
            $appointment->reminders()->create([
                'type' => 'sms',
                'send_at' => $startTime->subHour(),
                'is_sent' => false
            ]);
        }
    }

    /**
     * Bildirişlər göndər
     */
    private function sendNotifications($appointment): void
    {
        // Bu hissə bildiriş sisteminin tətbiqi üçündür
        // Mail, SMS və ya push bildirişləri göndərilə bilər
        $appointment->doctor->user->notify(
            type: NotificationTypeEnum::AppointmentCreated,
            data: [
                'title' => t('enums.notification_types.appointment_create'),
                'content' => str(t('enums.notification_types.appointment_create_description'))
                    ->replace(
                        [
                            '{fullname}',
                            '{start_date}',
                            '{end_date}',
                        ],
                        [
                            $appointment->fullname,
                            $appointment->start_time->format('d.m.Y H:i'),
                            $appointment->end_time->format('d.m.Y H:i')
                        ]
                    ),
            ]
        );
    }

    /**
     * Ləğv əməliyyatlarını idarə et
     */
    private function handleCancellation($appointment): void
    {
        // Ödəniş geri qaytarılması
        if ($appointment->is_paid && $appointment->payment) {
            // Refund logic
        }
    }

    /**
     * Tamamlanma əməliyyatlarını idarə et
     */
    private function handleCompletion($appointment): void
    {
        // Rəy üçün dəvət göndər
        // İstatistikaları yenilə
    }

    /**
     * Bu günkü randevular
     */
    public function getTodayAppointments(): Collection
    {
        return $this->repository->getTodayAppointments();
    }

    /**
     * Randevu statistikaları
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->repository->getStatistics($filters);
    }

    /**
     * Xəstənin randevularını əldə edir (Screen 1 üçün)
     */
    public function getPatientAppointments(array $filters): LengthAwarePaginator
    {
        $query = $this->repository->model->newQuery()
            ->with([
                'doctor.user',
                'doctor.category',
                'clinic',
                'service',
                'payment',
                'reviews'
            ])
            ->where('patient_id', $filters['patient_id']);

        // Status filtri
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('appointment_status', $filters['status']);
        }

        // Tarix aralığı filtri
        if (!empty($filters['date_range'])) {
            $this->applyDateRangeFilter($query, $filters['date_range']);
        }

        // Sıralama
        $sortField = $filters['sort'] ?? 'start_time';
        $direction = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $direction);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Tarix aralığı filtrini tətbiq edir
     */
    private function applyDateRangeFilter($query, string $dateRange): void
    {
        $now = now();

        switch ($dateRange) {
            case 'last_week':
                $query->whereBetween('start_time', [$now->copy()->subWeek(), $now]);
                break;
            case 'last_month':
                $query->whereBetween('start_time', [$now->copy()->subMonth(), $now]);
                break;
            case 'last_three_months':
                $query->whereBetween('start_time', [$now->copy()->subMonths(3), $now]);
                break;
            case 'last_six_months':
                $query->whereBetween('start_time', [$now->copy()->subMonths(6), $now]);
                break;
            case 'last_year':
                $query->whereBetween('start_time', [$now->copy()->subYear(), $now]);
                break;
            case 'all_time':
                // Heç bir məhdudiyyət tətbiq etmə
                break;
            default:
                // Default olaraq son 6 ay
                $query->whereBetween('start_time', [$now->copy()->subMonths(6), $now]);
        }
    }

    /**
     * Xəstənin randevu statistikalarını qaytarır
     */
    public function getPatientStats(int $patientId): array
    {
        $baseQuery = $this->repository->model->newQuery()->where('patient_id', $patientId);

        return [
            'total_appointments' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('appointment_status', 'completed')->count(),
            'cancelled' => (clone $baseQuery)->where('appointment_status', 'cancelled')->count(),
            'upcoming' => (clone $baseQuery)->where('start_time', '>', now())
                ->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled'])
                ->count(),
            'pending' => (clone $baseQuery)->where('appointment_status', 'pending')->count(),
            'no_show' => (clone $baseQuery)->where('appointment_status', 'no-show')->count(),

            // Bu ay və keçən ay müqayisəsi
            'this_month' => (clone $baseQuery)->whereMonth('start_time', now()->month)
                ->whereYear('start_time', now()->year)->count(),
            'last_month' => (clone $baseQuery)->whereMonth('start_time', now()->subMonth()->month)
                ->whereYear('start_time', now()->subMonth()->year)->count(),

            // Ödəniş statistikaları
            'total_spent' => (clone $baseQuery)->where('is_paid', true)->sum('price'),
            'pending_payments' => (clone $baseQuery)->where('is_paid', false)
                ->whereNotIn('appointment_status', ['cancelled', 'no-show'])
                ->sum('price'),
        ];
    }

    /**
     * UUID ilə xəstənin randevusunu tapır
     */
    public function getAppointmentByUuid(string $uuid, int $patientId, $userType = 'patient'): ?Appointment
    {
        return $this->repository->model->newQuery()
            ->with([
                'doctor.user',
                'doctor.category',
                'clinic',
                'service',
                'payment',
                'reviews',
                'reminders'
            ])
            ->where('uuid', $uuid)
            ->where($userType . '_id', $patientId)
            ->first();
    }

    /**
     * Randevu ləğv edilə bilər mi? (Screen 2 üçün)
     */
    public function canCancel(Appointment $appointment): bool
    {
        // Artıq ləğv edilmiş və ya tamamlanmış randevular ləğv edilə bilməz
        if (in_array($appointment->appointment_status, ['cancelled', 'completed', 'no-show'])) {
            return false;
        }

        // Randevu vaxtından ən azı 2 saat əvvəl ləğv edilə bilər
        if ($appointment->start_time && $appointment->start_time->diffInHours(now()) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Randevuya rəy yazıla bilər mi? (Screen 3 üçün)
     */
    public function canReview(Appointment $appointment): bool
    {
        // Yalnız tamamlanmış randevular üçün rəy yazıla bilər
        if ($appointment->appointment_status !== 'completed') {
            return false;
        }

        // Artıq rəy yazılıbsa, yenidən yazıla bilməz
        if ($appointment->reviews->isNotEmpty()) {
            return false;
        }

        // Randevu bitdikdən sonra 30 gün ərzində rəy yazıla bilər
        if ($appointment->end_time && $appointment->end_time->diffInDays(now()) > 30) {
            return false;
        }

        return true;
    }

    /**
     * Randevu ləğv edir (Screen 2 üçün)
     * @throws Exception
     */
    public function cancelAppointment(Appointment $appointment, array $cancelData): Appointment
    {
        DB::beginTransaction();
        try {
            // Cancel səbəblərini hazırlayırıq
            $reasons = $cancelData['reasons'] ?? [];
            $reasonTexts = [];

            foreach ($reasons as $reason) {
                $reasonTexts[] = match ($reason) {
                    'doctor_dislike' => 'Həkimi bəyənmirəm',
                    'time_conflict' => 'Vaxtım uyğun gəlmir',
                    'found_another_doctor' => 'Başqa həkim tapdım',
                    'personal_reason' => 'Şəxsi səbəbə görə',
                    'other' => 'Digər səbəb',
                    default => $reason
                };
            }

            if (!empty($cancelData['custom_reason'])) {
                $reasonTexts[] = 'Digər: ' . $cancelData['custom_reason'];
            }

            $finalReason = implode(', ', $reasonTexts);

            if (!empty($cancelData['note'])) {
                $finalReason .= ' | Qeyd: ' . $cancelData['note'];
            }

            // Randevu statusunu yeniləyirik
            $appointment->update([
                'appointment_status' => AppointmentStatusEnum::Cancelled,
                'cancel_reason' => $finalReason,
                'cancelled_at' => now(),
            ]);

            // Həkim və klinikaya bildiriş göndəririk
            $this->sendCancellationNotifications($appointment, $cancelData['cancelled_by'] ?? 'patient');

            // Ödəniş geri qaytarılması (əgər tələb olunarsa)
            $this->handleRefundIfNeeded($appointment);

            DB::commit();
            return $appointment->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ləğv bildirişlərini göndərir
     */
    private function sendCancellationNotifications(Appointment $appointment, string $cancelledBy): void
    {
        // TODO: Notification service ilə həkim və klinikaya bildiriş göndər
        // Bu hissə notification sistemindən asılıdır
    }

    /**
     * Lazım olduqda geri ödəmə prosesini başladır
     */
    private function handleRefundIfNeeded(Appointment $appointment): void
    {
        if (!$appointment->is_paid || !$appointment->payment) {
            return;
        }

        // Randevu 24 saaatdan çox əvvəl ləğv edilirsə, tam geri ödəmə
        $hoursUntilAppointment = $appointment->start_time->diffInHours(now());

        if ($hoursUntilAppointment >= 24) {
            // TODO: Payment service ilə tam refund prosesi

        } elseif ($hoursUntilAppointment >= 4) {
            // 4-24 saat arası 50% geri ödəmə
            // TODO: Payment service ilə qismən refund prosesi
        }

        // 4 saatdan az qalıbsa geri ödəmə yoxdur
    }

    /**
     * Filter seçimlərini qaytarır (front üçün)
     */
    public function getFilters(): array
    {
        return [
            'statuses' => collect(AppointmentStatusEnum::getValues())->map(function ($status) {
                return [
                    'value' => $status,
                    'label' => AppointmentStatusEnum::getDescription($status)
                ];
            })->toArray(),
            'date_ranges' => [
                ['value' => 'last_week', 'label' => 'Son həftə'],
                ['value' => 'last_month', 'label' => 'Son ay'],
                ['value' => 'last_three_months', 'label' => 'Son 3 ay'],
                ['value' => 'last_six_months', 'label' => 'Son 6 ay'],
                ['value' => 'last_year', 'label' => 'Son il'],
                ['value' => 'all_time', 'label' => 'Bütün vaxtlar']
            ]
        ];
    }
}
