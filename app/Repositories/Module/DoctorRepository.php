<?php

namespace App\Repositories\Module;

use App\Models\Doctor;
use App\Repositories\BaseRepository;
use App\Services\Filter\DoctorFilter;
use App\Services\Module\DoctorService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DoctorRepository extends BaseRepository
{
    public function __construct(Doctor $model)
    {
        parent::__construct($model);
        $this->setFilter(new DoctorFilter(request()));
        $this->with = [
            'user',
            'category',
            'subcategory',
            'educations',
            'experiences',
            'certificates',
            'languages',
            'clinics',
            'schedules.clinic',
            'services'
        ];
    }

    /**
     * Həkim yaradılarkən əlavə əməliyyatlar
     */
    protected function afterCreate($model): void
    {
        // Təhsil məlumatlarının əlavə edilməsi
        if (request()->has('educations')) {
            $this->createEducations($model, request()->get('educations'));
        }

        // İş təcrübəsinin əlavə edilməsi
        if (request()->has('experiences')) {
            $this->createExperiences($model, request()->get('experiences'));
        }

        // Sertifikatların əlavə edilməsi
        if (request()->has('certificates')) {
            $this->createCertificates($model, request()->get('certificates'));
        }

        // Dil biliklərinın əlavə edilməsi
        if (request()->has('languages')) {
            $this->createLanguages($model, request()->get('languages'));
        }

        // Klinikalarla əlaqənin qurulması
        if (request()->has('clinics')) {
            $this->syncClinics($model, request()->get('clinics'));
        }

        // İş cədvəlinin yaradılması
        if (request()->has('schedules')) {
            $this->createSchedules($model, request()->get('schedules'));
        }

        // Xidmətlərin əlavə edilməsi
        if (request()->has('services')) {
            $this->syncServices($model, request()->get('services'));
        }
    }

    /**
     * Həkim yenilənərkən əlavə əməliyyatlar
     */
    protected function afterUpdate($model): void
    {
        // Təhsil məlumatlarının yenilənməsi
        if (request()->has('educations')) {
            $this->updateEducations($model, request()->get('educations'));
        }

        // İş təcrübəsinin yenilənməsi
        if (request()->has('experiences')) {
            $this->updateExperiences($model, request()->get('experiences'));
        }

        // Sertifikatların yenilənməsi
        if (request()->has('certificates')) {
            $this->updateCertificates($model, request()->get('certificates'));
        }

        // Dil biliklərinın yenilənməsi
        if (request()->has('languages')) {
            $this->updateLanguages($model, request()->get('languages'));
        }

        // Klinikalarla əlaqənin yenilənməsi
        if (request()->has('clinics')) {
            $this->syncClinics($model, request()->get('clinics'));
        }

        // İş cədvəlinin yenilənməsi
        if (request()->has('schedules')) {
            $this->updateSchedules($model, request()->get('schedules'));
        }

        // Xidmətlərin yenilənməsi
        if (request()->has('services')) {
            $this->syncServices($model, request()->get('services'));
        }
    }

    /**
     * Təhsil məlumatlarının yaradılması
     */
    private function createEducations(Doctor $doctor, array $educations): void
    {
        foreach ($educations as $education) {
            $doctor->educations()->create($education);
        }
    }

    /**
     * İş təcrübəsinin yaradılması
     */
    private function createExperiences(Doctor $doctor, array $experiences): void
    {
        foreach ($experiences as $experience) {
            $doctor->experiences()->create($experience);
        }
    }

    /**
     * Sertifikatların yaradılması
     */
    private function createCertificates(Doctor $doctor, array $certificates): void
    {
        foreach ($certificates as $certificate) {
            $doctor->certificates()->create($certificate);
        }
    }

    /**
     * Dil biliklərinın yaradılması
     */
    private function createLanguages(Doctor $doctor, array $languages): void
    {
        foreach ($languages as $language) {
            $doctor->languages()->create($language);
        }
    }

    /**
     * İş cədvəlinin yaradılması
     */
    private function createSchedules(Doctor $doctor, array $schedules): void
    {
        foreach ($schedules as $schedule) {
            $doctor->schedules()->create($schedule);
        }
    }

    /**
     * Klinikalarla əlaqənin qurulması
     */
    private function syncClinics(Doctor $doctor, array $clinics): void
    {
        $syncData = [];
        foreach ($clinics as $clinic) {
            $syncData[$clinic['clinic_id']] = [
                'start_date' => $clinic['start_date'] ?? null,
                'end_date' => $clinic['end_date'] ?? null,
                'is_main_workplace' => $clinic['is_main_workplace'] ?? false,
                'is_active' => $clinic['is_active'] ?? true,
                'note' => $clinic['note'] ?? null,
            ];
        }
        $doctor->clinics()->sync($syncData);
    }

    /**
     * Xidmətlərin sinxronlaşdırılması
     */
    private function syncServices(Doctor $doctor, array $services): void
    {
        // Əvvəlki xidmətləri sil
        $doctor->services()->detach();

        // Yeni xidmətləri əlavə et
        foreach ($services as $service) {
            $doctor->services()->attach($service['service_id'], [
                'clinic_id' => $service['clinic_id'],
                'price' => $service['price'] ?? null,
                'duration' => $service['duration'] ?? null,
                'description' => $service['description'] ?? null,
                'is_active' => $service['is_active'] ?? true,
            ]);
        }
    }

    /**
     * Təhsil məlumatlarının yenilənməsi
     */
    private function updateEducations(Doctor $doctor, array $educations): void
    {
        // Mövcud təhsil məlumatlarını sil
        $doctor->educations()->delete();

        // Yeni təhsil məlumatlarını əlavə et
        $this->createEducations($doctor, $educations);
    }

    /**
     * İş təcrübəsinin yenilənməsi
     */
    private function updateExperiences(Doctor $doctor, array $experiences): void
    {
        // Mövcud təcrübə məlumatlarını sil
        $doctor->experiences()->delete();

        // Yeni təcrübə məlumatlarını əlavə et
        $this->createExperiences($doctor, $experiences);
    }

    /**
     * Sertifikatların yenilənməsi
     */
    private function updateCertificates(Doctor $doctor, array $certificates): void
    {
        // Mövcud sertifikatları sil
        $doctor->certificates()->delete();

        // Yeni sertifikatları əlavə et
        $this->createCertificates($doctor, $certificates);
    }

    /**
     * Dil biliklərinın yenilənməsi
     */
    private function updateLanguages(Doctor $doctor, array $languages): void
    {
        // Mövcud dil biliklərinı sil
        $doctor->languages()->delete();

        // Yeni dil biliklərinı əlavə et
        $this->createLanguages($doctor, $languages);
    }

    /**
     * İş cədvəlinin yenilənməsi
     */
    private function updateSchedules(Doctor $doctor, array $schedules): void
    {
        // Mövcud iş cədvəlini sil
        $doctor->schedules()->delete();

        // Yeni iş cədvəlini əlavə et
        $this->createSchedules($doctor, $schedules);
    }

    /**
     * İxtisasa görə həkimləri tap
     */
    public function findByCategory(int $categoryId, $limit = 0): Collection
    {
        return $this->model->query()
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
                $query->orWhere('sub_category_id', $categoryId);
            })
            ->with($this->with)
            ->when($limit > 0, function ($query) use ($limit) {
                $query->limit($limit);
            })
            ->isActive()
            ->get();
    }

    /**
     * Random həkimləri tap
     */
    public function findRandom(): Collection
    {
        return $this->model->query()
            ->with($this->with)
            ->isActive()
            ->limit(4)
            ->get();
    }

    /**
     * Klinikaya görə həkimləri tap
     */
    public function findByClinic(int $clinicId): Collection
    {
        return $this->model->whereHas('clinics', function($query) use ($clinicId) {
            $query->where('clinic_id', $clinicId)
                ->where('is_active', true);
        })->with($this->with)->get();
    }

    /**
     * Təsdiqlənmiş həkimləri tap
     */
    public function findVerified(): Collection
    {
        return $this->model->where('is_verified', true)
            ->with($this->with)
            ->get();
    }

    /**
     * Populyar həkimləri tap
     */
    public function findPopular(int $limit = 10): Collection
    {
        return $this->model->where('is_featured', true)
            ->orderByRaw('average_rating / NULLIF(total_ratings, 0) DESC')
            ->limit($limit)
            ->with($this->with)
            ->get();
    }

    /**
     * Həkimin mövcudluğunu yoxla
     */
    public function checkAvailability(int $doctorId, string $date, string $time): bool
    {
        $doctor = $this->findById($doctorId);

        // İş cədvəlini yoxla
        $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');
        $hasSchedule = $doctor->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->exists();

        if (!$hasSchedule) {
            return false;
        }

        // Məşğulluğu yoxla
        $datetime = \Carbon\Carbon::parse($date . ' ' . $time);
        $isUnavailable = $doctor->unavailabilities()
            ->where('start_datetime', '<=', $datetime)
            ->where('end_datetime', '>=', $datetime)
            ->exists();

        return !$isUnavailable;
    }

    /**
     * Filtrlər üçün məlumatları əldə et
     */
    public function filters(): array
    {
        return [
            'cities' => \App\Models\City::active()->get(['id', 'translates'])->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
            ]),
            'regions' => \App\Models\Region::active()->get(['id', 'translates'])->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
            ]),
            'categories' => app(CategoryRepository::class)->fetchCategoryByParent()->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'children' => $i->children,
            ]),
            'languages' => [
                ['id' => 'az', 'name' => 'Azərbaycan'],
                ['id' => 'en', 'name' => 'İngilis'],
                ['id' => 'ru', 'name' => 'Rus'],
                ['id' => 'tr', 'name' => 'Türk'],
            ],
            'experience_ranges' => [
                ['id' => '0-2', 'name' => '0-2 il'],
                ['id' => '3-5', 'name' => '3-5 il'],
                ['id' => '6-10', 'name' => '6-10 il'],
                ['id' => '11-15', 'name' => '11-15 il'],
                ['id' => '16+', 'name' => '16+ il'],
            ],
            'fee_ranges' => [
                ['id' => '0-50', 'name' => '0-50 AZN'],
                ['id' => '51-100', 'name' => '51-100 AZN'],
                ['id' => '101-200', 'name' => '101-200 AZN'],
                ['id' => '201+', 'name' => '201+ AZN'],
            ]
        ];
    }

    public function doctorSearch($request): LengthAwarePaginator
    {
        $query = Doctor::with([
            'user',
            'category',
            'subcategory',
            'doctorClinics' => function($q) {
                $q->where('is_active', true)->with('clinic');
            },
            'reviews'
        ]);

        // Filtrasiya tətbiq et
        if ($this->filter) {
            $query = $this->filter->apply($query);
        }

        $doctors = $query->paginate($request->limit ?? 10);

        // Hər həkim üçün nearest slots və available days əlavə et
        $doctorService = app(DoctorService::class);

        $doctors->getCollection()->transform(function ($doctor) use ($doctorService) {
            // Nearest slots əlavə et
            $doctor->nearest_slots = $doctorService->getNearestAvailableSlots($doctor, 3);

            // Available days əlavə et (opsional - performance üçün yalnız lazım olduqda)
            $doctor->available_days = $doctorService->getAvailableDaysForNextDays($doctor, 7);

            return $doctor;
        });

        return $doctors;
    }

    public function doctorView($slug)
    {
        return app(DoctorService::class)->getDoctorWithAvailability($slug);
    }
}
