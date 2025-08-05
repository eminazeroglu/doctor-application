<?php

namespace App\Services\Module;

use App\Repositories\Module\DoctorRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

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
    public function getDoctorsByCategory(int $categoryId): Collection
    {
        return $this->repository->findByCategory($categoryId);
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
            $startDate = \Carbon\Carbon::parse($experience['start_date']);
            $endDate = isset($experience['end_date'])
                ? \Carbon\Carbon::parse($experience['end_date'])
                : now();

            $totalYears += $startDate->diffInYears($endDate);
        }

        return $totalYears;
    }
}
