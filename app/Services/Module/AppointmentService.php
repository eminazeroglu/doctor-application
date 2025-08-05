<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Exceptions\BaseException;
use App\Models\DoctorSchedule;
use App\Models\DoctorUnavailability;
use App\Repositories\Module\AppointmentRepository;
use App\Services\BaseCrudService;
use Carbon\Carbon;
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

        DB::beginTransaction();
        try {
            // Randevu yarat
            $appointment = $this->repository->create($data);

            // Əlavə əməliyyatlar
            $this->handlePostCreation($appointment);

            DB::commit();
            return $appointment;

        } catch (\Exception $e) {
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

        } catch (\Exception $e) {
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

        } catch (\Exception $e) {
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
        $dayOfWeek = $startTime->format('l'); // Monday, Tuesday, etc.

        $schedule = DoctorSchedule::query()
            ->where('doctor_id', $data['doctor_id'])
            ->where('day_of_week', $dayOfWeek)
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
            ->where('start_datetime', '<=', $data['start_time'])
            ->where('end_datetime', '>=', $data['start_time'])
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
        $this->sendNotifications($appointment, 'created');
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
    private function sendNotifications($appointment, $type): void
    {
        // Bu hissə bildiriş sisteminin tətbiqi üçündür
        // Mail, SMS və ya push bildirişləri göndərilə bilər
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
}
