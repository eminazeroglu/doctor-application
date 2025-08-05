<?php

namespace App\Services\Module;

use App\Models\Patient;
use App\Repositories\Module\PatientRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PatientService extends BaseCrudService
{
    public function __construct(PatientRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new patient with user data
     */
    public function createWithUser(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * Update patient and user data
     */
    public function updateWithUser(int $id, array $data): Model
    {
        $patient = $this->findById($id);

        // Update user data if provided
        if (isset($data['user'])) {
            $userData = $data['user'];
            unset($data['user']);

            $patient->user->update($userData);
        }

        return $this->update($id, $data);
    }

    /**
     * Get patient by user ID
     */
    public function findByUserId(int $userId): ?Model
    {
        return $this->repository->findByUserId($userId);
    }

    /**
     * Search patients by query
     */
    public function searchPatients(string $query): Collection
    {
        return $this->repository->searchByQuery($query);
    }

    /**
     * Get patients with upcoming appointments
     */
    public function getPatientsWithUpcomingAppointments(): Collection
    {
        return $this->repository->withUpcomingAppointments();
    }

    /**
     * Get patients with active medications
     */
    public function getPatientsWithActiveMedications(): Collection
    {
        return $this->repository->withActiveMedications();
    }

    /**
     * Get patients by blood type
     */
    public function getPatientsByBloodType(string $bloodType): Collection
    {
        return $this->repository->getByBloodType($bloodType);
    }

    /**
     * Get patients with expired insurance
     */
    public function getPatientsWithExpiredInsurance(): Collection
    {
        return $this->repository->withExpiredInsurance();
    }

    /**
     * Get emergency contacts for a patient
     */
    public function getEmergencyContacts(int $patientId): Collection
    {
        return $this->repository->getEmergencyContacts($patientId);
    }

    /**
     * Get patient statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Get patient with full profile (all relations loaded)
     */
    public function getFullProfile(int $id): Model
    {
        return $this->repository->findByIdWithFullProfile($id);
    }

    /**
     * Add medical record to patient
     */
    public function addMedicalRecord(int $patientId, array $recordData): Model
    {
        $patient = $this->findById($patientId);
        $recordData['patient_id'] = $patientId;

        return $patient->medicalRecords()->create($recordData);
    }

    /**
     * Add medication to patient
     */
    public function addMedication(int $patientId, array $medicationData): Model
    {
        $patient = $this->findById($patientId);
        $medicationData['patient_id'] = $patientId;

        return $patient->medications()->create($medicationData);
    }

    /**
     * Add allergy to patient
     */
    public function addAllergy(int $patientId, array $allergyData): Model
    {
        $patient = $this->findById($patientId);
        $allergyData['patient_id'] = $patientId;

        return $patient->patientAllergies()->create($allergyData);
    }

    /**
     * Add family member to patient
     */
    public function addFamilyMember(int $patientId, array $familyMemberData): Model
    {
        $patient = $this->findById($patientId);
        $familyMemberData['patient_id'] = $patientId;

        return $patient->familyMembers()->create($familyMemberData);
    }

    /**
     * Add document to patient
     */
    public function addDocument(int $patientId, array $documentData): Model
    {
        $patient = $this->findById($patientId);
        $documentData['patient_id'] = $patientId;

        return $patient->documents()->create($documentData);
    }

    /**
     * Add favorite doctor for patient
     */
    public function addFavoriteDoctor(int $patientId, int $doctorId, ?string $note = null): void
    {
        $patient = $this->findById($patientId);

        $patient->favoriteDoctors()->syncWithoutDetaching([
            $doctorId => ['note' => $note]
        ]);
    }

    /**
     * Remove favorite doctor for patient
     */
    public function removeFavoriteDoctor(int $patientId, int $doctorId): void
    {
        $patient = $this->findById($patientId);
        $patient->favoriteDoctors()->detach($doctorId);
    }

    /**
     * Add favorite clinic for patient
     */
    public function addFavoriteClinic(int $patientId, int $clinicId, ?string $note = null): void
    {
        $patient = $this->findById($patientId);

        $patient->favoriteClinics()->syncWithoutDetaching([
            $clinicId => ['note' => $note]
        ]);
    }

    /**
     * Remove favorite clinic for patient
     */
    public function removeFavoriteClinic(int $patientId, int $clinicId): void
    {
        $patient = $this->findById($patientId);
        $patient->favoriteClinics()->detach($clinicId);
    }

    /**
     * Calculate BMI for patient
     */
    public function calculateBMI(int $patientId): ?float
    {
        $patient = $this->findById($patientId);
        return $patient->bmi;
    }

    /**
     * Check if patient insurance is expired
     */
    public function isInsuranceExpired(int $patientId): ?bool
    {
        $patient = $this->findById($patientId);
        return $patient->is_insurance_expired;
    }

    /**
     * Get filters for patient list
     */
    public function filters(): array
    {
        return $this->repository->filters();
    }

    /**
     * Bulk update patient statuses
     */
    public function bulkUpdateStatus(array $patientIds, string $status): int
    {
        return Patient::whereIn('id', $patientIds)
            ->whereHas('user')
            ->get()
            ->each(function($patient) use ($status) {
                $patient->user->update(['status' => $status]);
            })
            ->count();
    }

    /**
     * Export patients data
     */
    public function exportPatients(array $filters = []): Collection
    {
        // Apply filters if provided and return patient data for export
        $query = $this->repository->model->with(['user']);

        if (!empty($filters)) {
            // Apply filters here based on filter array
            if (isset($filters['blood_type'])) {
                $query->where('blood_type', $filters['blood_type']);
            }

            if (isset($filters['gender'])) {
                $query->whereHas('user', function($q) use ($filters) {
                    $q->where('gender', $filters['gender']);
                });
            }

            if (isset($filters['age_range'])) {
                // Implement age range filter logic
            }
        }

        return $query->get();
    }
}
