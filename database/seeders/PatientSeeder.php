<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\Category;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientDocument;
use App\Models\PatientFamilyMember;
use App\Models\PatientMedicalRecord;
use App\Models\PatientMedication;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Əvvəlcə Doctor, Clinic və Service-lərin mövcudluğunu yoxlayırıq
            $doctors = Doctor::with('user')->take(50)->get();
            $clinics = Clinic::take(20)->get();
            $services = Service::take(30)->get();
            $categories = Category::take(10)->get();

            if ($doctors->isEmpty() || $clinics->isEmpty() || $services->isEmpty()) {
                $this->command->error('Doctors, Clinics və ya Services tapılmadı. Əvvəlcə onları yaradın.');
                return;
            }

            $this->command->info('200 Patient yaradılır...');

            for ($i = 1; $i <= 200; $i++) {
                // User yaradırıq

                $user = $this->createUser($i);

                // Patient yaradırıq
                $patient = $this->createPatient($user);

                // Tibbi qeydlər
                $this->createMedicalRecords($patient, $doctors, $services);

                // Tibbi sənədlər
                $this->createDocuments($patient, $doctors);

                // Favori həkimlər və klinikalar
                $this->createFavorites($patient, $doctors, $clinics);

                // Ailə üzvləri
                $this->createFamilyMembers($patient);

                // Dərman qeydləri
                $this->createMedications($patient, $doctors);

                // Allergiya qeydləri
                $this->createAllergies($patient);

                if ($i % 20 == 0) {
                    $this->command->info("$i patient yaradıldı...");
                }
            }

            DB::commit();
            $this->command->info('200 Patient uğurla yaradıldı!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Xəta baş verdi: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * User yaradır
     */
    private function createUser(int $index): User
    {
        $firstName = fake('az_AZ')->firstName();
        $lastName = fake('az_AZ')->lastName();
        $email = strtolower(str_replace(' ', '.', $firstName . '.' . $lastName)) . $index . '@example.com';

        if ($index === 1) {
            $email = 'patient@example.com';
        }

        return User::create([
            'name' => $firstName,
            'surname' => $lastName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'username' => strtolower(str_replace(' ', '_', $firstName . '_' . $lastName)) . '_' . $index,
            'phone' => '+994' . fake()->numberBetween(50, 99) . fake()->numberBetween(1000000, 9999999),
            'gender' => fake()->randomElement(GenderEnum::getValues()),
            'birthdate' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'user_type' => UserTypeEnum::User,
            'status' => UserStatusEnum::Active,
            'is_system' => false,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Patient yaradır
     */
    private function createPatient(User $user): Patient
    {
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $insuranceProviders = ['ARDAK', 'FIDAN Sığorta', 'Qala Sığorta', 'AXA', 'AMEA Sığorta'];

        return Patient::create([
            'user_id' => $user->id,
            'medical_history' => fake()->optional(0.7)->paragraph(),
            'allergies' => fake()->optional(0.3)->sentence(),
            'chronic_diseases' => fake()->optional(0.2)->sentence(),
            'current_medications' => fake()->optional(0.4)->sentence(),
            'family_medical_history' => fake()->optional(0.5)->paragraph(),
            'blood_type' => fake()->optional(0.8)->randomElement($bloodTypes),
            'height' => fake()->optional(0.9)->numberBetween(150, 200),
            'weight' => fake()->optional(0.9)->numberBetween(50, 120),
            'emergency_contact_name' => fake()->optional(0.8)->name(),
            'emergency_contact_phone' => fake()->optional(0.8)->phoneNumber(),
            'emergency_contact_relation' => fake()->optional(0.8)->randomElement(['parent', 'spouse', 'sibling', 'child']),
            'insurance_provider' => fake()->optional(0.6)->randomElement($insuranceProviders),
            'insurance_policy_number' => fake()->optional(0.6)->numerify('POL-########'),
            'insurance_expiry_date' => fake()->optional(0.6)->dateTimeBetween('now', '+2 years'),
            'additional_info' => [
                'smoking' => fake()->boolean(20),
                'alcohol' => fake()->boolean(30),
                'exercise_frequency' => fake()->randomElement(['daily', 'weekly', 'monthly', 'never']),
            ]
        ]);
    }

    /**
     * Tibbi qeydlər yaradır
     */
    private function createMedicalRecords(Patient $patient, $doctors, $services): void
    {
        $recordTypes = ['diagnosis', 'checkup', 'test_result', 'consultation', 'treatment'];
        $diagnoses = [
            'Hipertensiya', 'Diabetes', 'Bronxit', 'Qastrit', 'Migren',
            'Artrit', 'Anemiya', 'Allergiya', 'Depressiya', 'İnsomnia'
        ];

        for ($i = 0; $i < fake()->numberBetween(1, 5); $i++) {
            PatientMedicalRecord::create([
                'patient_id' => $patient->id,
                'doctor_id' => fake()->optional(0.8)->randomElement($doctors)?->id,
                'record_type' => fake()->randomElement($recordTypes),
                'diagnosis' => fake()->optional(0.7)->randomElement($diagnoses),
                'description' => fake()->paragraph(),
                'treatment' => fake()->optional(0.6)->sentence(),
                'prescription' => fake()->optional(0.5)->sentence(),
                'notes' => fake()->optional(0.4)->sentence(),
                'record_date' => fake()->dateTimeBetween('-2 years', 'now'),
                'is_private' => fake()->boolean(10),
            ]);
        }
    }

    /**
     * Tibbi sənədlər yaradır
     */
    private function createDocuments(Patient $patient, $doctors): void
    {
        $documentTypes = ['lab_result', 'x_ray', 'prescription', 'mri', 'ct_scan', 'ultrasound'];
        $fileTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        for ($i = 0; $i < fake()->numberBetween(0, 3); $i++) {
            PatientDocument::create([
                'patient_id' => $patient->id,
                'doctor_id' => fake()->optional(0.7)->randomElement($doctors)?->id,
                'document_type' => fake()->randomElement($documentTypes),
                'title' => fake()->sentence(),
                'description' => fake()->optional(0.6)->paragraph(),
                'document_date' => fake()->dateTimeBetween('-1 year', 'now'),
                'file_path' => 'medical-documents/' . fake()->uuid() . '.pdf',
                'file_type' => fake()->randomElement($fileTypes),
                'file_size' => fake()->numberBetween(50000, 5000000),
                'is_verified' => fake()->boolean(70),
                'is_private' => fake()->boolean(20),
            ]);
        }
    }

    /**
     * Favori həkim və klinikalar yaradır
     */
    private function createFavorites(Patient $patient, $doctors, $clinics): void
    {
        // Favori həkimlər
        $favoriteDocCount = fake()->numberBetween(0, 5);
        if ($favoriteDocCount > 0) {
            $selectedDoctors = $doctors->random(min($favoriteDocCount, $doctors->count()));

            foreach ($selectedDoctors as $doctor) {
                $patient->favoriteDoctors()->attach($doctor->id, [
                    'note' => fake()->optional(0.3)->sentence(),
                ]);
            }
        }

        // Favori klinikalar
        $favoriteClinicCount = fake()->numberBetween(0, 3);
        if ($favoriteClinicCount > 0) {
            $selectedClinics = $clinics->random(min($favoriteClinicCount, $clinics->count()));

            foreach ($selectedClinics as $clinic) {
                $patient->favoriteClinics()->attach($clinic->id, [
                    'note' => fake()->optional(0.3)->sentence(),
                ]);
            }
        }
    }

    /**
     * Ailə üzvləri yaradır
     */
    private function createFamilyMembers(Patient $patient): void
    {
        $relations = ['parent', 'child', 'spouse', 'sibling'];

        for ($i = 0; $i < fake()->numberBetween(0, 4); $i++) {
            PatientFamilyMember::create([
                'patient_id' => $patient->id,
                'name' => fake()->firstName(),
                'surname' => fake()->lastName(),
                'birthdate' => fake()->optional(0.8)->dateTimeBetween('-80 years', 'now'),
                'gender' => fake()->optional(0.9)->randomElement(GenderEnum::getValues()),
                'relation' => fake()->randomElement($relations),
                'phone' => fake()->optional(0.6)->phoneNumber(),
                'email' => fake()->optional(0.4)->email(),
                'notes' => fake()->optional(0.3)->sentence(),
                'is_emergency_contact' => fake()->boolean(30),
                'is_dependent' => fake()->boolean(20),
            ]);
        }
    }

    /**
     * Dərman qeydləri yaradır
     */
    private function createMedications(Patient $patient, $doctors): void
    {
        $medications = [
            'Paracetamol', 'Ibuprofen', 'Aspirin', 'Amoxicillin', 'Metformin',
            'Atenolol', 'Simvastatin', 'Omeprazole', 'Salbutamol', 'Insulin'
        ];

        for ($i = 0; $i < fake()->numberBetween(0, 4); $i++) {
            $startDate = fake()->dateTimeBetween('-6 months', 'now');
            $isActive = fake()->boolean(60);

            PatientMedication::create([
                'patient_id' => $patient->id,
                'doctor_id' => fake()->optional(0.8)->randomElement($doctors)?->id,
                'medication_name' => fake()->randomElement($medications),
                'dosage' => fake()->randomElement(['500mg', '250mg', '100mg', '50mg', '10mg']),
                'frequency' => fake()->randomElement(['Gündə 1 dəfə', 'Gündə 2 dəfə', 'Gündə 3 dəfə', 'Həftədə 1 dəfə']),
                'instructions' => fake()->sentence(),
                'start_date' => $startDate,
                'end_date' => $isActive ? null : fake()->dateTimeBetween($startDate, 'now'),
                'reason' => fake()->optional(0.7)->sentence(),
                'side_effects' => fake()->optional(0.3)->sentence(),
                'is_active' => $isActive,
                'notes' => fake()->optional(0.4)->sentence(),
            ]);
        }
    }

    /**
     * Allergiya qeydləri yaradır
     */
    private function createAllergies(Patient $patient): void
    {
        $allergens = [
            'Penicillin', 'Aspirin', 'Polen', 'Pişik tükü', 'İt tükü',
            'Fıstıq', 'Dəniz məhsulları', 'Süd', 'Yumurta', 'Buğda',
            'Latex', 'Nikkel', 'Arı sancması', 'Formaldehid', 'Parfüm'
        ];

        $allergenTypes = ['drug', 'food', 'environmental', 'animal'];
        $severities = ['mild', 'moderate', 'severe'];

        $allergyCount = fake()->numberBetween(0, 3);

        if ($allergyCount > 0) {
            // Unikal allergenləri seçirik
            $selectedAllergens = collect($allergens)->shuffle()->take($allergyCount);

            foreach ($selectedAllergens as $allergen) {
                PatientAllergy::create([
                    'patient_id' => $patient->id,
                    'allergen_name' => $allergen,
                    'allergen_type' => fake()->randomElement($allergenTypes),
                    'severity' => fake()->optional(0.8)->randomElement($severities),
                    'reactions' => fake()->optional(0.7)->sentence(),
                    'diagnosis_date' => fake()->optional(0.6)->dateTimeBetween('-5 years', 'now'),
                    'notes' => fake()->optional(0.4)->sentence(),
                ]);
            }
        }
    }
}
