<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\PatientResource;
use App\Services\Module\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
class PatientController extends ApiController
{
    public function __construct(PatientService $service)
    {
        parent::__construct($service, 'patient');
        $this->setResource(PatientResource::class);
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            // User data validation
            'user.name' => 'required|string|max:255',
            'user.surname' => 'required|string|max:255',
            'user.email' => 'required|email|unique:users,email',
            'user.phone' => 'nullable|string|max:20',
            'user.gender' => 'nullable|in:male,female',
            'user.birthdate' => 'nullable|date|before:today',
            'user.password' => 'required|string|min:8|confirmed',

            // Patient specific data
            'medical_history' => 'nullable|string',
            'allergies' => 'nullable|string',
            'chronic_diseases' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'family_medical_history' => 'nullable|string',
            'additional_info' => 'nullable|array',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'height' => 'nullable|numeric|min:50|max:300',
            'weight' => 'nullable|numeric|min:10|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expiry_date' => 'nullable|date|after:today',
        ];
    }

    /**
     * Store a new patient
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $this->validateRequest($request, $this->storeRules(), $this->storeMessages());

            $data = $this->service->createWithUser($request->all());

            return response()->json($this->toResource($data), 201);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Update a patient
     * @throws ValidationException
     */
    public function update(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, $this->updateRules(), $this->updateMessages());

            $data = $this->service->updateWithUser($id, $request->all());

            return response()->json($this->toResource($data));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Get patient full profile with all relations
     */
    public function profile($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $patient = $this->service->getFullProfile($id);
            return response()->json($this->toResource($patient));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Search patients
     */
    public function search(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $query = $request->get('query', '');
            $patients = $this->service->searchPatients($query);

            return response()->json([
                'data' => $this->toResource($patients),
                'total' => $patients->count()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Get patients statistics
     */
    public function statistics(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $stats = $this->service->getStatistics();
            return response()->json($stats);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Get patients with upcoming appointments
     */
    public function upcomingAppointments(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $patients = $this->service->getPatientsWithUpcomingAppointments();
            return response()->json($this->toResource($patients));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Get patients with active medications
     */
    public function activeMedications(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $patients = $this->service->getPatientsWithActiveMedications();
            return response()->json($this->toResource($patients));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Get patients by blood type
     */
    public function bloodType(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $bloodType = $request->get('type');
            $patients = $this->service->getPatientsByBloodType($bloodType);
            return response()->json($this->toResource($patients));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Get patients with expired insurance
     */
    public function expiredInsurance(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $patients = $this->service->getPatientsWithExpiredInsurance();
            return response()->json($this->toResource($patients));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Get emergency contacts for a patient
     */
    public function emergencyContacts($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $contacts = $this->service->getEmergencyContacts($id);
            return response()->json($contacts);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Add medical record to patient
     * @throws ValidationException
     */
    public function addMedicalRecord(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $this->validateRequest($request, [
                'record_type' => 'required|string',
                'diagnosis' => 'nullable|string',
                'description' => 'nullable|string',
                'treatment' => 'nullable|string',
                'prescription' => 'nullable|string',
                'notes' => 'nullable|string',
                'record_date' => 'required|date',
                'doctor_id' => 'nullable|exists:doctors,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'is_private' => 'boolean',
            ]);

            $record = $this->service->addMedicalRecord($id, $request->all());
            return response()->json($record, 201);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Add medication to patient
     * @throws ValidationException
     */
    public function addMedication(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $this->validateRequest($request, [
                'medication_name' => 'required|string',
                'dosage' => 'nullable|string',
                'frequency' => 'nullable|string',
                'instructions' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'reason' => 'nullable|string',
                'side_effects' => 'nullable|string',
                'notes' => 'nullable|string',
                'doctor_id' => 'nullable|exists:doctors,id',
                'appointment_id' => 'nullable|exists:appointments,id',
            ]);

            $medication = $this->service->addMedication($id, $request->all());
            return response()->json($medication, 201);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Add allergy to patient
     * @throws ValidationException
     */
    public function addAllergy(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $this->validateRequest($request, [
                'allergen_name' => 'required|string',
                'allergen_type' => 'required|in:food,drug,environmental,animal,insect,other',
                'severity' => 'nullable|in:mild,moderate,severe',
                'reactions' => 'nullable|string',
                'diagnosis_date' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            $allergy = $this->service->addAllergy($id, $request->all());
            return response()->json($allergy, 201);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Add family member to patient
     * @throws ValidationException
     */
    public function addFamilyMember(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $this->validateRequest($request, [
                'member_patient_id' => 'nullable|exists:patients,id',
                'name' => 'required_without:member_patient_id|string',
                'surname' => 'required_without:member_patient_id|string',
                'birthdate' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female',
                'relation' => 'required|in:parent,child,spouse,sibling,grandparent,grandchild,other',
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
                'notes' => 'nullable|string',
                'is_emergency_contact' => 'boolean',
                'is_dependent' => 'boolean',
            ]);

            $familyMember = $this->service->addFamilyMember($id, $request->all());
            return response()->json($familyMember, 201);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Add document to patient
     * @throws ValidationException
     */
    public function addDocument(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $this->validateRequest($request, [
                'document_type' => 'required|string',
                'title' => 'required|string',
                'description' => 'nullable|string',
                'document_date' => 'required|date',
                'file' => 'required|file|max:10240', // 10MB max
                'doctor_id' => 'nullable|exists:doctors,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'is_verified' => 'boolean',
                'is_private' => 'boolean',
            ]);

            // Handle file upload here
            $documentData = $request->except('file');
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('patient-documents', 'public');
                $documentData['file_path'] = $path;
                $documentData['file_type'] = $file->getMimeType();
                $documentData['file_size'] = $file->getSize();
            }

            $document = $this->service->addDocument($id, $documentData);
            return response()->json($document, 201);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Add favorite doctor for patient
     * @throws ValidationException
     */
    public function addFavoriteDoctor(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'doctor_id' => 'required|exists:doctors,id',
                'note' => 'nullable|string',
            ]);

            $this->service->addFavoriteDoctor($id, $request->doctor_id, $request->note);
            return response()->json(['message' => 'Favori həkim əlavə edildi']);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Remove favorite doctor for patient
     */
    public function removeFavoriteDoctor(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $doctorId = $request->get('doctor_id');
            $this->service->removeFavoriteDoctor($id, $doctorId);
            return response()->json(['message' => 'Favori həkim silindi']);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Add favorite clinic for patient
     * @throws ValidationException
     */
    public function addFavoriteClinic(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'clinic_id' => 'required|exists:clinics,id',
                'note' => 'nullable|string',
            ]);

            $this->service->addFavoriteClinic($id, $request->clinic_id, $request->note);
            return response()->json(['message' => 'Favori klinika əlavə edildi']);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Remove favorite clinic for patient
     */
    public function removeFavoriteClinic(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $clinicId = $request->get('clinic_id');
            $this->service->removeFavoriteClinic($id, $clinicId);
            return response()->json(['message' => 'Favori klinika silindi']);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Export patients data
     */
    public function export(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $filters = $request->get('filters', []);
            $patients = $this->service->exportPatients($filters);

            return response()->json([
                'data' => $this->toResource($patients),
                'total' => $patients->count()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Bulk status update
     * @throws ValidationException
     */
    public function bulkStatusUpdate(Request $request): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'patient_ids' => 'required|array',
                'patient_ids.*' => 'exists:patients,id',
                'status' => 'required|string',
            ]);

            $count = $this->service->bulkUpdateStatus($request->patient_ids, $request->status);

            return response()->json([
                'message' => "{$count} xəstənin statusu yeniləndi",
                'updated_count' => $count
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
