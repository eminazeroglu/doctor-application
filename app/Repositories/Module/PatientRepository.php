<?php

namespace App\Repositories\Module;

use App\Models\Patient;
use App\Repositories\BaseRepository;
use App\Services\Filter\PatientFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PatientRepository extends BaseRepository
{
    public function __construct(Patient $model)
    {
        parent::__construct($model);
        $this->setFilter(new PatientFilter(request()));
        $this->with = [
            'user',
            'medicalRecords.doctor',
            'medicalRecords.appointment',
            'documents',
            'appointments',
            'medications',
            'patientAllergies',
            'familyMembers',
        ];
    }

    /**
     * Override filters method to return specific patient filters
     */
    public function filters(): array
    {
        return [
            'blood_types' => $this->getBloodTypes(),
            'insurance_providers' => $this->getInsuranceProviders(),
            'genders' => $this->getGenders(),
            'age_ranges' => $this->getAgeRanges(),
        ];
    }

    /**
     * Find patient by user ID
     */
    public function findByUserId(int $userId): ?Model
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * Get patients with upcoming appointments
     */
    public function withUpcomingAppointments(): Collection
    {
        return $this->model->with('upcomingAppointments')->get();
    }

    /**
     * Get patients with active medications
     */
    public function withActiveMedications(): Collection
    {
        return $this->model->with('activeMedications')->get();
    }

    /**
     * Search patients by name or phone
     */
    public function searchByQuery(string $query): Collection
    {
        return $this->model
            ->whereHas('user', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('surname', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->get();
    }

    /**
     * Get patients by blood type
     */
    public function getByBloodType(string $bloodType): Collection
    {
        return $this->model->where('blood_type', $bloodType)->get();
    }

    /**
     * Get patients with expired insurance
     */
    public function withExpiredInsurance(): Collection
    {
        return $this->model
            ->whereNotNull('insurance_expiry_date')
            ->where('insurance_expiry_date', '<', now())
            ->get();
    }

    /**
     * Get emergency contacts for a patient
     */
    public function getEmergencyContacts(int $patientId): Collection
    {
        return $this->model
            ->find($patientId)
            ->emergencyContacts()
            ->get();
    }

    /**
     * Get patient statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_patients' => $this->model->count(),
            'active_patients' => $this->model->whereHas('user', function($q) {
                $q->where('status', 'active');
            })->count(),
            'patients_with_insurance' => $this->model->whereNotNull('insurance_provider')->count(),
            'patients_with_allergies' => $this->model->whereHas('patientAllergies')->count(),
            'patients_by_gender' => $this->model
                ->join('users', 'patients.user_id', '=', 'users.id')
                ->selectRaw('users.gender, COUNT(*) as count')
                ->groupBy('users.gender')
                ->pluck('count', 'gender')
                ->toArray(),
            'patients_by_blood_type' => $this->model
                ->selectRaw('blood_type, COUNT(*) as count')
                ->whereNotNull('blood_type')
                ->groupBy('blood_type')
                ->pluck('count', 'blood_type')
                ->toArray(),
        ];
    }

    /**
     * Get available blood types
     */
    private function getBloodTypes(): array
    {
        return [
            ['id' => 'A+', 'name' => 'A Pozitiv'],
            ['id' => 'A-', 'name' => 'A Neqativ'],
            ['id' => 'B+', 'name' => 'B Pozitiv'],
            ['id' => 'B-', 'name' => 'B Neqativ'],
            ['id' => 'AB+', 'name' => 'AB Pozitiv'],
            ['id' => 'AB-', 'name' => 'AB Neqativ'],
            ['id' => 'O+', 'name' => 'O Pozitiv'],
            ['id' => 'O-', 'name' => 'O Neqativ'],
        ];
    }

    /**
     * Get insurance providers
     */
    private function getInsuranceProviders(): array
    {
        return $this->model
            ->whereNotNull('insurance_provider')
            ->distinct()
            ->pluck('insurance_provider')
            ->map(function($provider) {
                return ['id' => $provider, 'name' => $provider];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get gender options
     */
    private function getGenders(): array
    {
        return [
            ['id' => 'male', 'name' => 'Kişi'],
            ['id' => 'female', 'name' => 'Qadın'],
        ];
    }

    /**
     * Get age ranges
     */
    private function getAgeRanges(): array
    {
        return [
            ['id' => '0-18', 'name' => '0-18 yaş'],
            ['id' => '19-35', 'name' => '19-35 yaş'],
            ['id' => '36-50', 'name' => '36-50 yaş'],
            ['id' => '51-65', 'name' => '51-65 yaş'],
            ['id' => '65+', 'name' => '65+ yaş'],
        ];
    }

    /**
     * Override create method to handle user creation
     */
    public function create(array $data)
    {
        // If user_id is not provided, create a new user
        if (!isset($data['user_id']) && isset($data['user'])) {
            $userData = $data['user'];
            unset($data['user']);

            $user = app(\App\Repositories\Module\UserRepository::class)->create($userData);
            $data['user_id'] = $user->id;
        }

        return parent::create($data);
    }

    /**
     * Load full patient profile with all relations
     */
    public function findByIdWithFullProfile(int $id): Model
    {
        return $this->model
            ->with([
                'user',
                'medicalRecords' => function($query) {
                    $query->orderBy('record_date', 'desc')->limit(10);
                },
                'documents' => function($query) {
                    $query->orderBy('document_date', 'desc')->limit(10);
                },
                'activeMedications',
                'patientAllergies',
                'familyMembers',
                'upcomingAppointments.doctor.user',
                'upcomingAppointments.clinic',
                'upcomingAppointments.service',
                'favoriteDoctors.user',
                'favoriteClinics'
            ])
            ->findOrFail($id);
    }
}
