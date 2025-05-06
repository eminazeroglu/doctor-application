<?php

namespace Database\Seeders;

use App\Enums\CredentialTypeEnum;
use App\Models\BlockedCredential;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BlockedCredentialSeeder extends Seeder
{
    protected array $sampleEmails = [
        'spam@example.com',
        'abuse@spammer.com',
        'scammer123@fakemail.com',
        'badactor@malicious.net',
        'unwanted@spam.org'
    ];

    protected array $samplePhones = [
        '+994501234567',
        '+994551234567',
        '+994701234567',
        '+994771234567',
        '+994991234567'
    ];

    protected array $sampleIps = [
        '192.168.1.100',
        '10.0.0.50',
        '172.16.0.100',
        '8.8.8.8',
        '1.1.1.1'
    ];

    protected array $sampleReasons = [
        'Spam göndərmə',
        'Təhlükəsizlik pozuntusu',
        'Şübhəli aktivlik',
        'Çoxlu uğursuz giriş cəhdi',
        'Sistem qaydalarının pozulması',
        'Avtomatlaşdırılmış bot aktivliyi',
        'Fişinq cəhdi',
        'İstifadəçi şikayətləri'
    ];

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        BlockedCredential::truncate();

        $admins = User::whereHas('role', function($q) {
            $q->where('name', 'admin');
        })->get();

        if ($admins->isEmpty()) {
            $this->command->error('Admin istifadəçilər tapılmadı! UserSeeder-i işlədin.');
            return;
        }

        // Hər tip üçün bloklar yaradırıq
        foreach ([
                     CredentialTypeEnum::Email => $this->sampleEmails,
                     CredentialTypeEnum::Phone => $this->samplePhones,
                     CredentialTypeEnum::IP => $this->sampleIps
                 ] as $type => $values) {
            foreach ($values as $value) {
                $this->createBlock($type, $value, $admins->random());
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->command->info('BlockedCredential seed prosesi uğurla tamamlandı!');
    }

    protected function createBlock(string $type, string $value, User $admin): void
    {
        // Random blok növü seçirik
        $blockType = rand(1, 10) > 3 ? 'temporary' : 'permanent';

        // Blok müddətini təyin edirik
        $blockedUntil = $blockType === 'permanent' ? null : Carbon::now()->addDays(rand(7, 90));

        // Random səbəb seçirik
        $reason = $this->sampleReasons[array_rand($this->sampleReasons)];

        // Random tarix təyin edirik (son 30 gün ərzində)
        $createdAt = Carbon::now()->subDays(rand(1, 30));

        // Bloku yaradırıq
        BlockedCredential::create([
            'type' => $type,
            'value' => $value,
            'reason' => $reason,
            'blocked_until' => $blockedUntil,
            'is_active' => true,
            'created_by' => $admin->id,
            'created_by_name' => $admin->fullname,
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);
    }
}
