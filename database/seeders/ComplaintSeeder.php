<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatusEnum;
use App\Enums\ComplaintMessageStatusEnum;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use App\Models\Company;
use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ComplaintSeeder extends Seeder
{
    /**
     * Metodun məqsədi: Mövcud User, Company və Listing məlumatlarına əsasən çoxsaylı şikayət və mesaj yaradır.
     * Yüzlərlə şikayət və minlərlə mesaj əlavə edir.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('complaints')->truncate();
        DB::table('complaint_messages')->truncate();

        // Mövcud istifadəçiləri, şirkətləri və elanları alırıq
        $users = User::where('is_system', false)->get(); // Sistem istifadəçiləri xaric
        $admins = User::where('is_system', true)->get(); // Adminlər
        $companies = Company::all();
        $listings = Listing::all();

        // Əgər heç bir məlumat yoxdursa, xəbərdarlıq edib çıxaq
        if ($users->isEmpty() || $companies->isEmpty() || $listings->isEmpty() || $admins->isEmpty()) {
            $this->command->info("Xəta: Mövcud User, Company, Listing və ya admin yoxdur. Əvvəlcə bu cədvəlləri doldurun.\n");
            return;
        }

        $titles = [
            'Xidmət keyfiyyəti aşağıdır',
            'Məhsul vaxtında çatdırılmadı',
            'Yanlış məlumat verildi',
            'Pulumu geri qaytarmadılar',
            'Elan saxtadır',
            'Cavab verilmir',
        ];

        $descriptions = [
            'Bu şirkətdən məhsul aldım, amma 3 həftədir gəlib çıxmır. Niyə belə gecikir?',
            'Elanla bağlı zəng etdim, heç kim cavab vermədi. Bu necə işdir?',
            'Mənə dedilər ki, məhsul stokdadır, amma sonra yoxdur dedilər. Aldadıram?',
            'Şirkətə pul ödədim, amma heç bir xidmət göstərmədilər. Pulumu qaytarın!',
            'Bu elanda göstərilən qiymət yalandır, başqa qiymət dedilər.',
            'İstifadəçinin davranışı xoşuma gəlmədi, məni təhqir etdi.',
        ];

        $userMessages = [
            'Nə vaxta qədər yoxlanılacaq? Artıq 1 həftədir gözləyirəm!',
            'Mənə tez cavab verin, bu problem həll olunmalıdır.',
            'Əlavə məlumat vermək istəyirəm, necə edim?',
            'Bu şikayətimə baxılmır, nə vaxt cavab alacam?',
        ];

        $adminMessages = [
            'Şikayətiniz yoxlanılır, tezliklə cavab veriləcək.',
            'Problem həll olundu, əlavə sualınız varsa yazın.',
            'Şikayətiniz rədd edildi, çünki sübut yoxdur.',
            'Məsələ araşdırıldı, tərəflərlə əlaqə saxlanıldı.',
        ];

        // 500 şikayət yaradırıq
        for ($i = 1; $i <= 500; $i++) {
            $user = $users->random();
            $type = $this->getRandomComplaintableType();
            $complaintable = $this->getRandomComplaintable($type, $users, $companies, $listings);
            $status = $this->getRandomStatus();
            $date = Carbon::now()->subDays(rand(1, 180)); // Son 6 ay ərzində

            $complaint = Complaint::create([
                'user_id' => $user->id,
                'complaintable_type' => $type === 'user' ? User::class : ($type === 'company' ? Company::class : Listing::class),
                'complaintable_id' => $complaintable->id,
                'title' => $titles[array_rand($titles)],
                'description' => $descriptions[array_rand($descriptions)],
                'attachments' => rand(0, 1) ? json_encode(["files/complaint{$i}.pdf"]) : null,
                'status' => $status,
                'resolution_note' => in_array($status, [ComplaintStatusEnum::Resolved, ComplaintStatusEnum::Rejected]) ? "Həll/Rədd səbəbi: " . Str::random(20) : null,
                'resolved_by' => in_array($status, [ComplaintStatusEnum::Resolved, ComplaintStatusEnum::Rejected]) ? $admins->random()->id : null,
                'resolved_at' => in_array($status, [ComplaintStatusEnum::Resolved, ComplaintStatusEnum::Rejected]) ? $date->addDays(rand(1, 10)) : null,
                'created_at' => $date,
                'updated_at' => $date->addDays(rand(0, 10)),
            ]);

            // Hər şikayətə 1-5 arası random cavab əlavə edirik (ümumi ~1500 mesaj)
            $messageCount = rand(1, 5);
            for ($j = 1; $j <= $messageCount; $j++) {
                $isStaff = rand(0, 1); // 50% istifadəçi, 50% admin cavabı
                $sender = $isStaff ? $admins->random() : $user;

                $complaint->messages()->create([
                    'user_id' => $sender->id,
                    'message' => $isStaff ? $adminMessages[array_rand($adminMessages)] : $userMessages[array_rand($userMessages)],
                    'attachments' => rand(0, 1) ? json_encode(["files/message{$i}_{$j}.jpg"]) : null,
                    'status' => $this->getRandomMessageStatus(),
                    'is_staff_reply' => $isStaff,
                    'created_at' => $date->addHours(rand(1, 24)),
                    'updated_at' => $date->addHours(rand(1, 48)),
                ]);
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Metodun məqsədi: Random complaintable tipi qaytarır (user, company, listing).
     * Hər tipə bərabər ehtimal verir.
     */
    private function getRandomComplaintableType(): string
    {
        $random = rand(1, 3);
        return match ($random) {
            1 => 'user',
            2 => 'company',
            3 => 'listing',
        };
    }

    /**
     * Metodun məqsədi: Verilən tipə uyğun random complaintable obyekti qaytarır.
     * Mövcud məlumatlardan seçir.
     */
    private function getRandomComplaintable(string $type, $users, $companies, $listings)
    {
        return match ($type) {
            'user' => $users->random(),
            'company' => $companies->random(),
            'listing' => $listings->random(),
        };
    }

    /**
     * Metodun məqsədi: Random şikayət statusu qaytarır.
     * Fərqli statuslara müxtəlif ehtimallar verir.
     */
    private function getRandomStatus(): string
    {
        $random = rand(1, 100);
        return match (true) {
            $random <= 40 => ComplaintStatusEnum::Pending,    // 40%
            $random <= 70 => ComplaintStatusEnum::InProgress, // 30%
            $random <= 85 => ComplaintStatusEnum::Resolved,   // 15%
            $random <= 95 => ComplaintStatusEnum::Rejected,   // 10%
            default => ComplaintStatusEnum::Closed            // 5%
        };
    }

    /**
     * Metodun məqsədi: Random mesaj statusu qaytarır.
     * Fərqli statuslara müxtəlif ehtimallar verir.
     */
    private function getRandomMessageStatus(): string
    {
        $random = rand(1, 100);
        return match (true) {
            $random <= 50 => ComplaintMessageStatusEnum::Pending,    // 50%
            $random <= 85 => ComplaintMessageStatusEnum::Read,       // 35%
            $random <= 95 => ComplaintMessageStatusEnum::Responded,  // 10%
            default => ComplaintMessageStatusEnum::Hidden            // 5%
        };
    }
}
