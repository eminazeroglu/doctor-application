<?php

namespace App\Services\Module;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Repositories\Module\ReviewRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mockery\CountValidator\Exception;
use Throwable;

class ReviewService extends BaseCrudService
{
    public function __construct(ReviewRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Həkim üzrə rəyləri əldə edir
     */
    public function getByDoctor(int $doctorId): Collection
    {
        return $this->repository->findByDoctor($doctorId);
    }

    /**
     * Klinika üzrə rəyləri əldə edir
     */
    public function getByClinic(int $clinicId): Collection
    {
        return $this->repository->findByClinic($clinicId);
    }

    /**
     * Moderasiya gözləyən rəyləri əldə edir
     */
    public function getAwaitingModeration(): Collection
    {
        return $this->repository->findAwaitingModeration();
    }

    /**
     * Ən faydalı rəyləri əldə edir
     */
    public function getMostHelpful(int $limit = 10): Collection
    {
        return $this->repository->findMostHelpful($limit);
    }

    /**
     * Şikayət edilmiş rəyləri əldə edir
     */
    public function getReported(): Collection
    {
        return $this->repository->findReported();
    }

    /**
     * Rəy statistikalarını əldə edir
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Rəyi təsdiqlə
     */
    public function verify(int $id): bool
    {
        try {
            return $this->repository->verify($id);
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Rəyi moderasiya et
     */
    public function moderate(int $id): bool
    {
        try {
            return $this->repository->moderate($id);
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Rəy yaratma (əlavə validasiya ilə)
     * @throws Throwable
     */
    public function create(array $data): Model
    {
        try {
            DB::beginTransaction();

            // Əgər həkim rəyidirsə doctor_id təyin et, clinic_id null et
            if (!empty($data['doctor_id'])) {
                $data['clinic_id'] = null;
            }

            // Əgər klinika rəyidirsə clinic_id təyin et, doctor_id null et
            if (!empty($data['clinic_id'])) {
                $data['doctor_id'] = null;
            }

            // Default statuslar
            $data['is_moderated'] = $data['is_moderated'] ?? false;
            $data['is_verified'] = $data['is_verified'] ?? false;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_anonymous'] = $data['is_anonymous'] ?? false;

            // UUID avtomatik yaradılacaq (HasUuid trait-i sayəsində)
            $review = parent::create($data);

            // Əgər randevu ID-si verilmişsə, randevunu "reviewed" statusuna çevir
            if (!empty($data['appointment_id'])) {
                $this->markAppointmentAsReviewed($data['appointment_id']);
            }

            DB::commit();
            return $review;

        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    /**
     * Rəy yenilənməsi (əlavə validasiya ilə)
     * @throws Throwable
     */
    public function update(int $id, array $data): Model
    {
        try {
            DB::beginTransaction();

            // Mövcud rəyi əldə et
            $existingReview = $this->repository->findById($id);

            // Əgər həkim rəyidirsə doctor_id təyin et, clinic_id null et
            if (!empty($data['doctor_id'])) {
                $data['clinic_id'] = null;
            }

            // Əgər klinika rəyidirsə clinic_id təyin et, doctor_id null et
            if (!empty($data['clinic_id'])) {
                $data['doctor_id'] = null;
            }

            $review = parent::update($id, $data);

            DB::commit();
            return $review;

        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    /**
     * Toplu əməliyyatlar
     * @throws Throwable
     */
    public function bulk($type, $request): array
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return [
                'success' => false,
                'message' => 'Heç bir element seçilməyib',
                'affected_count' => 0
            ];
        }

        try {
            DB::beginTransaction();

            $result = match($type) {
                'moderate' => $this->repository->bulkModerate($ids),
                'verify' => $this->repository->bulkVerify($ids),
                'delete' => $this->bulkDelete($ids),
                'activate' => $this->bulkActivate($ids),
                'deactivate' => $this->bulkDeactivate($ids),
                'approve' => $this->bulkApprove($ids), // Həm moderate həm verify
                default => false
            };

            if ($result) {
                DB::commit();
                return [
                    'success' => true,
                    'message' => $this->getBulkMessage($type, count($ids)),
                    'type' => $type,
                    'affected_count' => count($ids)
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Əməliyyat zamanı xəta baş verdi',
                    'type' => $type,
                    'affected_count' => 0
                ];
            }

        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return [
                'success' => false,
                'message' => 'Sistem xətası: ' . $e->getMessage(),
                'type' => $type,
                'affected_count' => 0
            ];
        }
    }

    /**
     * Toplu silmə
     */
    private function bulkDelete(array $ids): bool
    {
        return $this->repository->updateWhere(
            [['id', 'in', $ids]],
            ['is_active' => false]
        );
    }

    /**
     * Toplu aktivləşdirmə
     */
    private function bulkActivate(array $ids): bool
    {
        return $this->repository->updateWhere(
            [['id', 'in', $ids]],
            ['is_active' => true]
        );
    }

    /**
     * Toplu deaktivləşdirmə
     */
    private function bulkDeactivate(array $ids): bool
    {
        return $this->repository->updateWhere(
            [['id', 'in', $ids]],
            ['is_active' => false]
        );
    }

    /**
     * Toplu təsdiqləmə (həm moderate həm verify)
     */
    private function bulkApprove(array $ids): bool
    {
        return $this->repository->updateWhere(
            [['id', 'in', $ids]],
            [
                'is_moderated' => true,
                'is_verified' => true,
                'is_active' => true
            ]
        );
    }

    /**
     * Randevunu rəy verilmiş kimi işarələ
     */
    private function markAppointmentAsReviewed(int $appointmentId): void
    {
        // Bu metod Appointment servisi ilə əlaqələndirilə bilər
        // İndi sadə olaraq appointment cədvəlini yeniləyirik
        Appointment::query()
            ->where('id', $appointmentId)
            ->update(['is_reviewed' => true]);
    }

    /**
     * Toplu əməliyyat mesajları
     */
    private function getBulkMessage(string $type, int $count): string
    {
        return match($type) {
            'moderate' => "{$count} rəy moderasiya edildi",
            'verify' => "{$count} rəy təsdiqləndi",
            'delete' => "{$count} rəy silindi",
            'activate' => "{$count} rəy aktivləşdirildi",
            'deactivate' => "{$count} rəy deaktivləşdirildi",
            'approve' => "{$count} rəy tam təsdiqləndi",
            default => "{$count} rəy üzərində əməliyyat icra edildi"
        };
    }

    /**
     * Həkim reytinqini hesabla və yenilə
     */
    public function updateDoctorRating(int $doctorId): void
    {
        try {
            $reviews = $this->repository->findByDoctor($doctorId);

            if ($reviews->isNotEmpty()) {
                $averageRating = $reviews->avg('rating');
                $totalReviews = $reviews->count();

                // Doctor cədvəlini yenilə
                Doctor::query()
                    ->where('id', $doctorId)
                    ->update([
                        'average_rating' => round($averageRating, 1),
                        'total_reviews' => $totalReviews,
                        'updated_at' => now()
                    ]);
            }
        } catch (Exception $e) {
            report($e);
        }
    }

    /**
     * Klinika reytinqini hesabla və yenilə
     */
    public function updateClinicRating(int $clinicId): void
    {
        try {
            $reviews = $this->repository->findByClinic($clinicId);

            if ($reviews->isNotEmpty()) {
                $averageRating = $reviews->avg('rating');
                $totalReviews = $reviews->count();

                // Clinic cədvəlini yenilə
                Clinic::query()
                    ->where('id', $clinicId)
                    ->update([
                        'average_rating' => round($averageRating, 1),
                        'total_reviews' => $totalReviews,
                        'updated_at' => now()
                    ]);
            }
        } catch (Exception $e) {
            report($e);
        }
    }

    /**
     * Rəy silmədən sonra reytinqləri yenilə
     */
    public function delete(int $id): bool
    {
        try {
            $review = $this->repository->findById($id);
            $doctorId = $review->doctor_id;
            $clinicId = $review->clinic_id;

            $result = parent::delete($id);

            if ($result) {
                // Reytinqləri yenilə
                if ($doctorId) {
                    $this->updateDoctorRating($doctorId);
                }
                if ($clinicId) {
                    $this->updateClinicRating($clinicId);
                }
            }

            return $result;
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Həkimə aid bütün rəyləri sil (həkim silinəndə)
     */
    public function deleteByDoctor(int $doctorId): bool
    {
        try {
            return $this->repository->updateWhere(
                [['doctor_id', '=', $doctorId]],
                ['is_active' => false]
            );
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Klinikaya aid bütün rəyləri sil (klinika silinəndə)
     */
    public function deleteByClinic(int $clinicId): bool
    {
        try {
            return $this->repository->updateWhere(
                [['clinic_id', '=', $clinicId]],
                ['is_active' => false]
            );
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Xəstəyə aid bütün rəyləri sil (xəstə silinəndə)
     */
    public function deleteByPatient(int $patientId): bool
    {
        try {
            return $this->repository->updateWhere(
                [['patient_id', '=', $patientId]],
                ['is_active' => false]
            );
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Filter seçimləri
     */
    public function filters(): array
    {
        return $this->repository->filters();
    }

    /**
     * Dashboard üçün rəy məlumatları
     */
    public function getDashboardData(): array
    {
        try {
            $statistics = $this->getStatistics();
            $awaitingModeration = $this->getAwaitingModeration();
            $reported = $this->getReported();
            $recent = $this->repository->findAll()->take(5);

            return [
                'statistics' => $statistics,
                'awaiting_moderation_count' => $awaitingModeration->count(),
                'reported_count' => $reported->count(),
                'recent_reviews' => $recent,
                'needs_attention' => $awaitingModeration->count() + $reported->count()
            ];
        } catch (Exception $e) {
            report($e);
            return [
                'statistics' => [],
                'awaiting_moderation_count' => 0,
                'reported_count' => 0,
                'recent_reviews' => collect([]),
                'needs_attention' => 0
            ];
        }
    }
}
