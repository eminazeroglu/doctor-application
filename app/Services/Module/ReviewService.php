<?php

namespace App\Services\Module;

use App\Exceptions\BaseException;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Review;
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
     * Rəy yaratma (əlavə validasiya ilə) - Admin panel üçün
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

            // Reytinqləri yenilə
            if (!empty($data['doctor_id'])) {
                $this->updateDoctorRating($data['doctor_id']);
            }
            if (!empty($data['clinic_id'])) {
                $this->updateClinicRating($data['clinic_id']);
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
            $oldDoctorId = $existingReview->doctor_id;
            $oldClinicId = $existingReview->clinic_id;

            // Əgər həkim rəyidirsə doctor_id təyin et, clinic_id null et
            if (!empty($data['doctor_id'])) {
                $data['clinic_id'] = null;
            }

            // Əgər klinika rəyidirsə clinic_id təyin et, doctor_id null et
            if (!empty($data['clinic_id'])) {
                $data['doctor_id'] = null;
            }

            $review = parent::update($id, $data);

            // Köhnə və yeni reytinqləri yenilə
            if ($oldDoctorId && $oldDoctorId !== $review->doctor_id) {
                $this->updateDoctorRating($oldDoctorId);
            }
            if ($oldClinicId && $oldClinicId !== $review->clinic_id) {
                $this->updateClinicRating($oldClinicId);
            }

            if ($review->doctor_id) {
                $this->updateDoctorRating($review->doctor_id);
            }
            if ($review->clinic_id) {
                $this->updateClinicRating($review->clinic_id);
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
     * Randevunu rəy verilmiş kimi işarələ - DÜZƏLDİLDİ
     */
    private function markAppointmentAsReviewed(int $appointmentId): void
    {
        Appointment::query()
            ->where('id', $appointmentId)
            ->update([
                'reviewed_at' => now()
            ]);
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
     * Həkim reytinqini hesabla və yenilə - DÜZƏLDİLDİ
     */
    public function updateDoctorRating(int $doctorId): void
    {
        try {
            $reviews = Review::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->where('is_moderated', true)
                ->get();

            if ($reviews->isNotEmpty()) {
                $totalRating = $reviews->sum('rating');
                $totalCount = $reviews->count();

                // Doctor cədvəlini yenilə
                Doctor::query()
                    ->where('id', $doctorId)
                    ->update([
                        'average_rating' => $totalRating, // Həkim modelində rating_average attribute-u hesablayır
                        'total_ratings' => $totalCount,
                        'updated_at' => now()
                    ]);
            }
        } catch (Exception $e) {
            report($e);
        }
    }

    /**
     * Klinika reytinqini hesabla və yenilə - DÜZƏLDİLDİ
     */
    public function updateClinicRating(int $clinicId): void
    {
        try {
            $reviews = Review::where('clinic_id', $clinicId)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->where('is_moderated', true)
                ->get();

            if ($reviews->isNotEmpty()) {
                $totalRating = $reviews->sum('rating');
                $totalCount = $reviews->count();

                // Clinic cədvəlini yenilə
                Clinic::query()
                    ->where('id', $clinicId)
                    ->update([
                        'rating' => $totalRating, // Klinika modelində average_rating attribute-u hesablayır
                        'ratings_count' => $totalCount,
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

    /**
     * Rəy yaradır (Screen 3 üçün - xəstə tərəfindən)
     * Bu metod appointment controller-dən çağırılır
     * @throws \Exception
     */
    public function createReview(array $reviewData): Review
    {
        DB::beginTransaction();
        try {
            // Əvvəlcədən rəy yazılıb-yazılmadığını yoxlayırıq
            $existingReview = Review::where('appointment_id', $reviewData['appointment_id'])
                ->where('patient_id', $reviewData['patient_id'])
                ->first();

            if ($existingReview) {
                throw new BaseException([
                    'message' => 'Bu randevu üçün artıq rəy yazılmışdır'
                ], 422);
            }

            // Review yaradırıq
            $review = Review::create([
                'patient_id' => $reviewData['patient_id'],
                'appointment_id' => $reviewData['appointment_id'],
                'doctor_id' => $reviewData['doctor_id'],
                'clinic_id' => $reviewData['clinic_id'] ?? null,
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'] ?? null,
                'is_verified' => true, // Randevudan gələn rəylər avtomatik təsdiqlənir
                'is_moderated' => true, // Randevudan gələn rəylər avtomatik moderasiya edilir
                'is_active' => true,
                'is_anonymous' => $reviewData['is_anonymous'] ?? false,
            ]);

            // Həkim reytinqini yeniləyirik
            $this->updateDoctorRating($reviewData['doctor_id']);

            // Klinika reytinqini yeniləyirik (əgər klinika qiymətləndirilmişdirsə)
            if (!empty($reviewData['clinic_id'])) {
                $this->updateClinicRating($reviewData['clinic_id']);
            }

            // Randevu statusunu yeniləyirik
            $this->markAppointmentAsReviewed($reviewData['appointment_id']);

            DB::commit();
            return $review->load(['patient.user', 'doctor.user', 'clinic']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Xəstənin yazdığı rəylər (xəstə panelində istifadə üçün)
     */
    public function getPatientReviews(int $patientId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Review::with(['doctor.user', 'clinic', 'appointment'])
            ->where('patient_id', $patientId)
            ->where('is_active', true);

        // Filter əgər varsa
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Həkim haqqında rəylər (public üçün)
     */
    public function getDoctorReviews(int $doctorId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Review::with(['patient.user', 'appointment'])
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->where('is_moderated', true);

        // Filter əgər varsa
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        // Anonim rəylərdə xəstə məlumatını gizlət
        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Klinika haqqında rəylər (public üçün)
     */
    public function getClinicReviews(int $clinicId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Review::with(['patient.user', 'doctor.user', 'appointment'])
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->where('is_moderated', true);

        // Filter əgər varsa
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Rəy statistikaları (public üçün)
     */
    public function getReviewStats(string $type, int $id): array
    {
        $field = $type === 'doctor' ? 'doctor_id' : 'clinic_id';

        $reviews = Review::where($field, $id)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->where('is_moderated', true)
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'percentage_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            ];
        }

        $total = $reviews->count();
        $averageRating = round($reviews->avg('rating'), 1);

        $distribution = [];
        $percentageDistribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->where('rating', $i)->count();
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;

            $distribution[$i] = $count;
            $percentageDistribution[$i] = $percentage;
        }

        return [
            'total_reviews' => $total,
            'average_rating' => $averageRating,
            'rating_distribution' => $distribution,
            'percentage_distribution' => $percentageDistribution,
        ];
    }

    /**
     * Rəy silmə (xəstə və ya admin tərəfindən)
     * @throws \Exception
     */
    public function deleteReview(int $reviewId, int $userId, string $userRole = 'patient'): bool
    {
        $review = Review::findOrFail($reviewId);

        // Yalnız yazan xəstə və ya admin silə bilər
        if ($userRole !== 'admin' && $review->patient->user_id !== $userId) {
            throw new BaseException([
                'message' => 'Bu rəyi silmək icazəniz yoxdur'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $doctorId = $review->doctor_id;
            $clinicId = $review->clinic_id;

            // Rəyi deaktiv edirik (tam silmirik)
            $review->update(['is_active' => false]);

            // Reytinqləri yenidən hesabla
            if ($doctorId) {
                $this->updateDoctorRating($doctorId);
            }
            if ($clinicId) {
                $this->updateClinicRating($clinicId);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
