<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Appointment;
use App\Models\AppointmentRecurring;
use App\Models\AppointmentReminder;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Clinic;
use App\Models\Service;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        $faker = Faker::create();

        // Mövcud məlumatları götürürük
        $doctors = Doctor::with('user')->get();
        $patients = Patient::with('user')->get();
        $clinics = Clinic::get();
        $services = Service::get();

        AppointmentRecurring::query()->truncate();
        AppointmentReminder::query()->truncate();
        Appointment::query()->truncate();

        if ($doctors->isEmpty() || $patients->isEmpty() || $clinics->isEmpty()) {
            $this->command->warn('Doctor, Patient və ya Clinic məlumatları tapılmadı. Əvvəlcə onları yaradın.');
            return;
        }

        $appointmentStatuses = [
            AppointmentStatusEnum::Pending => 15,     // 15%
            AppointmentStatusEnum::Confirmed => 35,   // 35%
            AppointmentStatusEnum::Completed => 25,   // 25%
            AppointmentStatusEnum::Cancelled => 10,   // 10%
            AppointmentStatusEnum::NoShow => 8,       // 8%
            AppointmentStatusEnum::Rescheduled => 7,  // 7%
        ];

        $consultationTypes = ['in_person', 'online', 'home_visit'];

        $complaints = [
            'Baş ağrısı və baş gicəllənməsi',
            'Diş ağrısı və iltihab',
            'Mədə problemləri',
            'Nəfəs darlığı',
            'Arxa və boyun ağrısı',
            'Dəri problemi və qaşıntı',
            'Qulaq ağrısı',
            'Gözlərdə yanma və qızartı',
            'Ürək döyüntüləri',
            'Yuxusuzluq problemi',
            'Stress və əsəbilik',
            'Qan təzyiqi yoxlanması',
            'Ümumi müayinə',
            'Kontrol müayinəsi',
            'Təcili müayinə'
        ];

        $this->command->info('200 appointment yaradılır...');

        for ($i = 0; $i < 3000; $i++) {
            // Status seçimi (ağırlıqlı)
            $status = $this->getWeightedRandomStatus($appointmentStatuses);

            // Tarixi statusuna görə müəyyən edirik
            $startTime = $this->generateAppointmentTime($status, $faker);
            $endTime = (clone $startTime)->addMinutes($faker->numberBetween(15, 90));

            $doctor = $doctors->random();
            $patient = $patients->random();
            $clinic = $clinics->random();
            $service = $services->isNotEmpty() ? $services->random() : null;

            // Qiymət hesablama
            $basePrice = $service ? $service->price : $faker->randomFloat(2, 20, 200);
            $price = $basePrice ?: $faker->randomFloat(2, 30, 150);

            // Ödəniş statusu (completed randevuların 80%-i ödənilmiş olsun)
            $isPaid = false;
            if (in_array($status, [AppointmentStatusEnum::Completed, AppointmentStatusEnum::Confirmed])) {
                $isPaid = $faker->boolean(80); // 80% ehtimal
            } elseif ($status === AppointmentStatusEnum::Rescheduled) {
                $isPaid = $faker->boolean(60); // 60% ehtimal
            }

            $isCanceled = $status === AppointmentStatusEnum::Cancelled;

            $appointmentData = [
                'uuid' => Str::uuid(),
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'fullname' => fake()->lastName . ' ' . $faker->firstName,
                'email' => fake()->email,
                'phone' => fake()->phoneNumber,
                'clinic_id' => $clinic->id,
                'service_id' => $service?->id,
                'appointment_status' => $status,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'complaint' => $faker->randomElement($complaints),
                'notes' => $faker->boolean(70) ? $faker->sentence($faker->numberBetween(5, 15)) : null,
                'price' => $price,
                'is_paid' => $isPaid,
                'cancel_reason' => $isCanceled ? $this->getCancelReason($faker) : null,
                'cancelled_at' => $isCanceled ? $faker->dateTimeBetween('now', '3months') : null,
                'cancelled_by' => $isCanceled ? fake()->randomElement(['doctor', 'patient']) : null,
                'location' => $clinic->address,
                'consultation_type' => $faker->randomElement($consultationTypes),
                'additional_info' => $faker->boolean(30) ? [
                    'special_requirements' => $faker->sentence(),
                    'emergency' => $faker->boolean(10),
                    'follow_up' => $faker->boolean(20)
                ] : null,
                'created_at' => $startTime->copy()->subDays($faker->numberBetween(1, 30)),
                'updated_at' => now(),
            ];

            $appointment = Appointment::create($appointmentData);

            // Əgər ödənilmişsə, Payment yaradırıq
            if ($isPaid) {
                $this->createPaymentForAppointment($appointment, $faker);
            }

            if ($i % 50 === 0) {
                $this->command->info("Yaradıldı: " . ($i + 1) . "/200");
            }
        }

        $this->command->info('✅ 200 appointment uğurla yaradıldı!');

        // Statistika göstər
        $this->showStatistics();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Ağırlıqlı təsadüfi status seçimi
     */
    private function getWeightedRandomStatus(array $statuses): string
    {
        $rand = mt_rand(1, 100);
        $sum = 0;

        foreach ($statuses as $status => $weight) {
            $sum += $weight;
            if ($rand <= $sum) {
                return $status;
            }
        }

        return AppointmentStatusEnum::Pending;
    }

    /**
     * Statusuna görə randevu vaxtı yaradır
     */
    private function generateAppointmentTime(string $status, $faker): Carbon
    {
        $now = now();

        switch ($status) {
            case AppointmentStatusEnum::Completed:
            case AppointmentStatusEnum::NoShow:
                // Keçmiş tarixlər
                return Carbon::instance($faker->dateTimeBetween('-3 months', '-1 day'));

            case AppointmentStatusEnum::Cancelled:
                // Qarışıq (keçmiş və gələcək)
                return Carbon::instance($faker->dateTimeBetween('-2 months', '+1 month'));

            case AppointmentStatusEnum::Pending:
            case AppointmentStatusEnum::Confirmed:
            case AppointmentStatusEnum::Rescheduled:
                // Əsasən gələcək tarixlər
                if ($faker->boolean(20)) {
                    // 20% keçmiş
                    return Carbon::instance($faker->dateTimeBetween('-1 month', 'now'));
                } else {
                    // 80% gələcək
                    return Carbon::instance($faker->dateTimeBetween('now', '+3 months'));
                }

            default:
                return Carbon::instance($faker->dateTimeBetween('-2 months', '+2 months'));
        }
    }

    /**
     * Ləğv səbəbləri
     */
    private function getCancelReason($faker): string
    {
        $reasons = [
            'Xəstə tərəfindən ləğv edildi',
            'Həkim tərəfindən ləğv edildi',
            'Fövqəladə hal',
            'Klinika bağlı idi',
            'Xəstəlik səbəbi',
            'Şəxsi səbəblər',
            'İş səbəbi',
            'Hava şəraiti',
            'Nəqliyyat problemi',
            'Başqa həkimə yönləndirildi'
        ];

        return $faker->randomElement($reasons);
    }

    /**
     * Randevu üçün ödəniş yaradır
     */
    private function createPaymentForAppointment(Appointment $appointment, $faker): void
    {
        $paymentMethods = [
            PaymentMethodEnum::Card => 60,      // 60%
            PaymentMethodEnum::Cash => 30,      // 30%
            PaymentMethodEnum::BankTransfer => 8, // 8%
            PaymentMethodEnum::EWallet => 2,    // 2%
        ];

        $paymentMethod = $this->getWeightedRandomPaymentMethod($paymentMethods, $faker);

        $payment = Payment::create([
            'uuid' => Str::uuid(),
            'user_id' => $appointment->patient->user_id,
            'amount' => $appointment->price,
            'currency' => 'AZN',
            'payment_method' => $paymentMethod,
            'payment_status' => PaymentStatusEnum::Completed,
            'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
            'description' => "Randevu ödənişi - Dr. {$appointment->doctor->user->name} {$appointment->doctor->user->surname}",
            'paid_at' => $appointment->start_time->copy()->subMinutes($faker->numberBetween(5, 60)),
            'additional_info' => [
                'appointment_id' => $appointment->id,
                'clinic_name' => $appointment->clinic->name,
                'service_name' => $appointment->service?->name,
            ],
            'created_at' => $appointment->created_at,
            'updated_at' => now(),
        ]);

        // Appointmenti yeniləyək
        $appointment->update(['payment_id' => $payment->id]);
    }

    /**
     * Ağırlıqlı ödəniş metodu seçimi
     */
    private function getWeightedRandomPaymentMethod(array $methods, $faker): string
    {
        $rand = mt_rand(1, 100);
        $sum = 0;

        foreach ($methods as $method => $weight) {
            $sum += $weight;
            if ($rand <= $sum) {
                return $method;
            }
        }

        return PaymentMethodEnum::Card;
    }

    /**
     * Statistikalar göstər
     */
    private function showStatistics(): void
    {
        $total = Appointment::count();
        $byStatus = Appointment::selectRaw('appointment_status, COUNT(*) as count')
            ->groupBy('appointment_status')
            ->get()
            ->pluck('count', 'appointment_status');

        $paid = Appointment::where('is_paid', true)->count();
        $withPayments = Payment::whereHas('appointment')->count();

        $this->command->info("\n📊 Statistikalar:");
        $this->command->info("Ümumi randevular: {$total}");
        $this->command->info("Ödənilmiş randevular: {$paid}");
        $this->command->info("Payment qeydləri: {$withPayments}");

        $this->command->info("\nStatus üzrə paylanma:");
        foreach ($byStatus as $status => $count) {
            $percentage = round(($count / $total) * 100, 1);
            $statusText = AppointmentStatusEnum::getDescription($status);
            $this->command->info("  • {$statusText}: {$count} ({$percentage}%)");
        }
    }
}
