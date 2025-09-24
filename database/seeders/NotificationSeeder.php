<?php

namespace Database\Seeders;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationDeviceTypeEnum;
use App\Enums\NotificationPreferenceTypeEnum;
use App\Enums\NotificationTemplateTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\UserTypeEnum;
use App\Models\NotificationDevice;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Notification məlumatlarını təmizləyir...');
        $this->clearNotificationData();

        $this->command->info('Yeni məlumatlar yaradılır...');
        $this->createNotificationTemplates();
        $this->createNotificationPreferences();
        $this->createNotificationDevices();
        $this->createSampleNotifications();
        $this->createNotificationLogs();

        $this->command->info('✅ Notification seeder uğurla tamamlandı!');
    }

    /**
     * Notification modulu ilə əlaqəli bütün məlumatları silir
     */
    private function clearNotificationData(): void
    {
        // Foreign key constraint-lərə görə düzgün sıra ilə silmək
        NotificationLog::truncate();
        $this->command->info('- notification_logs cədvəli təmizləndi');

        Notification::truncate();
        $this->command->info('- notifications cədvəli təmizləndi');

        NotificationDevice::truncate();
        $this->command->info('- notification_devices cədvəli təmizləndi');

        NotificationPreference::truncate();
        $this->command->info('- notification_preferences cədvəli təmizləndi');

        NotificationTemplate::truncate();
        $this->command->info('- notification_templates cədvəli təmizləndi');

        $this->command->info('🗑️  Bütün notification məlumatları silindi.');
    }

    /**
     * Notification template-lərini yaradır
     */
    private function createNotificationTemplates(): void
    {
        $templates = [
            // RANDEVU TEMPLATE-LƏRİ
            [
                'name' => 'Randevu Yaradıldı - E-poçt',
                'code' => 'appointment_created_email',
                'channel' => NotificationChannelEnum::Email,
                'type' => NotificationTemplateTypeEnum::Appointment,
                'subject' => 'Yeni Randevu Yaradıldı - {clinic_name}',
                'content' => 'Hörmətli {user_name},

{doctor_name} həkimi ilə {appointment_date} tarixində saat {appointment_time}-da {clinic_name} klinikasında randevunuz yaradıldı.

Xidmət: {service_name}
Qiymət: {service_price} AZN

Randevu detalları üçün hesabınıza daxil olun.

Hörmətlə,
Doctap.az komandası',
                'variables' => json_encode([
                    'user_name', 'doctor_name', 'clinic_name', 'appointment_date',
                    'appointment_time', 'service_name', 'service_price'
                ]),
            ],

            [
                'name' => 'Randevu Yaradıldı - Push',
                'code' => 'appointment_created_push',
                'channel' => NotificationChannelEnum::Push,
                'type' => NotificationTemplateTypeEnum::Appointment,
                'subject' => 'Yeni Randevu',
                'content' => '{doctor_name} həkimi ilə randevunuz {appointment_date} tarixinə təyin edildi.',
                'variables' => json_encode(['doctor_name', 'appointment_date']),
            ],

            [
                'name' => 'Randevu Təsdiqləndi - E-poçt',
                'code' => 'appointment_confirmed_email',
                'channel' => NotificationChannelEnum::Email,
                'type' => NotificationTemplateTypeEnum::Appointment,
                'subject' => 'Randevunuz Təsdiqləndi - {clinic_name}',
                'content' => 'Hörmətli {user_name},

{appointment_date} tarixində saat {appointment_time}-da {doctor_name} həkimi ilə randevunuz təsdiqləndi.

Randevu detalları:
📍 Yer: {clinic_name}
👨‍⚕️ Həkim: {doctor_name}
🏥 Xidmət: {service_name}
💰 Qiymət: {service_price} AZN

Randevu vaxtından 30 dəqiqə əvvəl klinikada olmanızı xahiş edirik.

Hörmətlə,
Doctap.az komandası',
                'variables' => json_encode([
                    'user_name', 'doctor_name', 'clinic_name', 'appointment_date',
                    'appointment_time', 'service_name', 'service_price'
                ]),
            ],

            [
                'name' => 'Randevu Təsdiqləndi - SMS',
                'code' => 'appointment_confirmed_sms',
                'channel' => NotificationChannelEnum::Sms,
                'type' => NotificationTemplateTypeEnum::Appointment,
                'subject' => null,
                'content' => 'Doctap.az: {appointment_date} {appointment_time} randevunuz təsdiqləndi. {doctor_name} - {clinic_name}. 30 dəq əvvəl gəlin.',
                'variables' => json_encode(['appointment_date', 'appointment_time', 'doctor_name', 'clinic_name']),
            ],

            [
                'name' => 'Randevu Ləğv Edildi - E-poçt',
                'code' => 'appointment_cancelled_email',
                'channel' => NotificationChannelEnum::Email,
                'type' => NotificationTemplateTypeEnum::Appointment,
                'subject' => 'Randevunuz Ləğv Edildi - {clinic_name}',
                'content' => 'Hörmətli {user_name},

{appointment_date} tarixində saat {appointment_time}-da {doctor_name} həkimi ilə randevunuz ləğv edildi.

Ləğv səbəbi: {cancellation_reason}

Yeni randevu təyin etmək üçün saytımıza daxil olun və ya bizim 012 123 45 67 nömrəsinə zəng edin.

Hörmətlə,
Doctap.az komandası',
                'variables' => json_encode([
                    'user_name', 'doctor_name', 'clinic_name', 'appointment_date',
                    'appointment_time', 'cancellation_reason'
                ]),
            ],

            [
                'name' => 'Randevu Xatırlatması - Push',
                'code' => 'appointment_reminder_push',
                'channel' => NotificationChannelEnum::Push,
                'type' => NotificationTemplateTypeEnum::Appointment,
                'subject' => 'Randevu Xatırlatması',
                'content' => '24 saat sonra {doctor_name} həkimi ilə randevunuz var. {clinic_name} - {appointment_time}',
                'variables' => json_encode(['doctor_name', 'clinic_name', 'appointment_time']),
            ],

            [
                'name' => 'Randevu Xatırlatması - SMS',
                'code' => 'appointment_reminder_sms',
                'channel' => NotificationChannelEnum::Sms,
                'type' => NotificationTemplateTypeEnum::Appointment,
                'subject' => null,
                'content' => 'Doctap.az xatırlatması: Sabah {appointment_time}-da {doctor_name} həkimi ilə randevunuz var. {clinic_name}',
                'variables' => json_encode(['appointment_time', 'doctor_name', 'clinic_name']),
            ],

            // RƏY TEMPLATE-LƏRİ
            [
                'name' => 'Yeni Rəy Alındı - E-poçt',
                'code' => 'review_received_email',
                'channel' => NotificationChannelEnum::Email,
                'type' => NotificationTemplateTypeEnum::Review,
                'subject' => 'Yeni Rəy Aldınız - {rating} ulduz',
                'content' => 'Hörmətli Dr. {doctor_name},

{patient_name} xəstəniz sizin haqqınızda {rating} ulduzlu rəy yazıb.

Rəy: "{review_text}"

Rəyə cavab vermək üçün hesabınıza daxil olun.

Hörmətlə,
Doctap.az komandası',
                'variables' => json_encode(['doctor_name', 'patient_name', 'rating', 'review_text']),
            ],

            [
                'name' => 'Rəyə Cavab Verildi - Push',
                'code' => 'review_response_push',
                'channel' => NotificationChannelEnum::Push,
                'type' => NotificationTemplateTypeEnum::Review,
                'subject' => 'Rəyinizə Cavab',
                'content' => '{doctor_name} həkimi rəyinizə cavab verdi.',
                'variables' => json_encode(['doctor_name']),
            ],

            // MESAJ TEMPLATE-LƏRİ
            [
                'name' => 'Yeni Mesaj - Push',
                'code' => 'message_received_push',
                'channel' => NotificationChannelEnum::Push,
                'type' => NotificationTemplateTypeEnum::Message,
                'subject' => 'Yeni Mesaj',
                'content' => '{sender_name} sizdən mesaj göndərdi: {message_preview}',
                'variables' => json_encode(['sender_name', 'message_preview']),
            ],

            [
                'name' => 'Yeni Mesaj - E-poçt',
                'code' => 'message_received_email',
                'channel' => NotificationChannelEnum::Email,
                'type' => NotificationTemplateTypeEnum::Message,
                'subject' => 'Yeni Mesaj - {sender_name}',
                'content' => 'Hörmətli {user_name},

{sender_name} sizdən yeni mesaj göndərib:

"{message_content}"

Cavab vermək üçün hesabınıza daxil olun.

Hörmətlə,
Doctap.az komandası',
                'variables' => json_encode(['user_name', 'sender_name', 'message_content']),
            ],

            // SİSTEM TEMPLATE-LƏRİ
            [
                'name' => 'Xoş Gəlmisiniz - E-poçt',
                'code' => 'welcome_email',
                'channel' => NotificationChannelEnum::Email,
                'type' => NotificationTemplateTypeEnum::System,
                'subject' => 'Doctap.az-a Xoş Gəlmisiniz!',
                'content' => 'Hörmətli {user_name},

Doctap.az platformasına xoş gəlmisiniz!

Artıq siz:
✅ Ən yaxşı həkimləri tapa bilərsiniz
✅ Onlayn randevu təyin edə bilərsiniz
✅ Həkimlərinizlə birbaşa əlaqə qura bilərsiniz
✅ Tibbi tarixçənizi saxlaya bilərsiniz

Profil doldurmaq üçün hesabınıza daxil olun.

Hörmətlə,
Doctap.az komandası',
                'variables' => json_encode(['user_name']),
            ],

            [
                'name' => 'Hesab Təsdiqləndi - Push',
                'code' => 'account_verified_push',
                'channel' => NotificationChannelEnum::Push,
                'type' => NotificationTemplateTypeEnum::System,
                'subject' => 'Hesab Təsdiqləndi',
                'content' => 'Təbriklər! Hesabınız uğurla təsdiqləndi. İndi randevu təyin edə bilərsiniz.',
                'variables' => json_encode([]),
            ],

            [
                'name' => 'Şifrə Dəyişdirildi - E-poçt',
                'code' => 'password_changed_email',
                'channel' => NotificationChannelEnum::Email,
                'type' => NotificationTemplateTypeEnum::System,
                'subject' => 'Şifrəniz Dəyişdirildi',
                'content' => 'Hörmətli {user_name},

Hesabınızın şifrəsi uğurla dəyişdirildi.

Əgər bu dəyişikliyi siz etməmisinizsə, dərhal bizim dəstək xidməti ilə əlaqə saxlayın.

Təhlükəsizlik üçün məsləhətlərimiz:
• Güclü şifrə istifadə edin
• Şifrənizi kimsə ilə paylaşmayın
• Ümumi kompüterlərdə hesabınızdan çıxmağı unutmayın

Hörmətlə,
Doctap.az komandası',
                'variables' => json_encode(['user_name']),
            ],

            // İSTİFADƏÇİ TEMPLATE-LƏRİ
            [
                'name' => 'Profil Yeniləndi - InApp',
                'code' => 'profile_updated_inapp',
                'channel' => NotificationChannelEnum::InApp,
                'type' => NotificationTemplateTypeEnum::User,
                'subject' => 'Profil Yeniləndi',
                'content' => 'Profiliniz uğurla yeniləndi.',
                'variables' => json_encode([]),
            ],

            [
                'name' => 'Yeni Həkim Əlavə Edildi - Push',
                'code' => 'new_doctor_added_push',
                'channel' => NotificationChannelEnum::Push,
                'type' => NotificationTemplateTypeEnum::User,
                'subject' => 'Yeni Həkim',
                'content' => '{specialization} ixtisasında yeni həkim əlavə edildi - Dr. {doctor_name}',
                'variables' => json_encode(['specialization', 'doctor_name']),
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::create($template);
        }

        $this->command->info('✅ Notification templates yaradıldı (' . count($templates) . ' ədəd).');
    }

    /**
     * İstifadəçilər üçün notification preferences yaradır
     */
    private function createNotificationPreferences(): void
    {
        $users = User::query()->where('is_system', 0)->get();

        $preferenceTypes = [
            NotificationPreferenceTypeEnum::Appointment,
            NotificationPreferenceTypeEnum::Review,
            NotificationPreferenceTypeEnum::Message,
            NotificationPreferenceTypeEnum::System,
        ];

        foreach ($users as $user) {
            foreach ($preferenceTypes as $type) {
                NotificationPreference::create([
                    'user_id' => $user->id,
                    'notification_type' => $type,
                    'email_enabled' => true,
                    'sms_enabled' => $type === NotificationPreferenceTypeEnum::Appointment, // Yalnız randevu üçün SMS
                    'push_enabled' => true,
                    'in_app_enabled' => true,
                ]);
            }
        }

        $this->command->info('✅ Notification preferences yaradıldı (' . ($users->count() * count($preferenceTypes)) . ' ədəd).');
    }

    /**
     * Test cihazları yaradır
     */
    private function createNotificationDevices(): void
    {
        $users = User::query()->where('is_system', 0)->get();

        $deviceTypes = [
            NotificationDeviceTypeEnum::Ios,
            NotificationDeviceTypeEnum::Android,
            NotificationDeviceTypeEnum::Web,
        ];

        $deviceNames = [
            'ios' => ['iPhone 15 Pro', 'iPhone 14', 'iPhone 13', 'iPad Pro'],
            'android' => ['Samsung Galaxy S24', 'Google Pixel 8', 'Xiaomi 14', 'Huawei P60'],
            'web' => ['Chrome Browser', 'Safari Browser', 'Firefox Browser', 'Edge Browser'],
        ];

        $totalDevices = 0;

        foreach ($users as $user) {
            // Hər istifadəçiyə 1-3 cihaz əlavə et
            $deviceCount = rand(1, 3);

            for ($i = 0; $i < $deviceCount; $i++) {
                $deviceType = $deviceTypes[array_rand($deviceTypes)];
                $deviceName = $deviceNames[$deviceType][array_rand($deviceNames[$deviceType])];

                NotificationDevice::create([
                    'user_id' => $user->id,
                    'device_token' => $this->generateDeviceToken($deviceType),
                    'device_type' => $deviceType,
                    'device_name' => $deviceName,
                    'app_version' => $this->getRandomAppVersion(),
                    'is_active' => rand(0, 10) > 1, // 90% aktiv
                    'last_used_at' => now()->subDays(rand(0, 30)),
                ]);

                $totalDevices++;
            }
        }

        $this->command->info('✅ Notification devices yaradıldı (' . $totalDevices . ' ədəd).');
    }

    /**
     * Nümunə notification-lar yaradır
     */
    private function createSampleNotifications(): void
    {
        $users = User::query()->where('is_system', 0)->get();

        $notificationData = [
            [
                'type' => NotificationTypeEnum::AppointmentCreated,
                'title' => 'Yeni Randevu Yaradıldı',
                'content' => 'Dr. Əli Məmmədov həkimi ilə 15 Dekabr 2024 tarixində saat 14:30-da randevunuz yaradıldı.',
                'icon' => 'calendar-plus',
                'action_url' => '/appointments/123',
                'action_text' => 'Randevuya bax',
                'data' => ['appointment_id' => 123, 'doctor_id' => 5],
            ],
            [
                'type' => NotificationTypeEnum::AppointmentConfirmed,
                'title' => 'Randevu Təsdiqləndi',
                'content' => 'Dr. Leyla Əliyeva həkimi ilə randevunuz təsdiqləndi. Vaxtında gəlməyi unutmayın.',
                'icon' => 'check-circle',
                'action_url' => '/appointments/124',
                'action_text' => 'Detallar',
                'data' => ['appointment_id' => 124],
            ],
            [
                'type' => NotificationTypeEnum::AppointmentReminder,
                'title' => 'Randevu Xatırlatması',
                'content' => '24 saat sonra Dr. Rəşad Həsənov həkimi ilə randevunuz var.',
                'icon' => 'clock',
                'action_url' => '/appointments/125',
                'action_text' => 'Randevuya bax',
                'data' => ['appointment_id' => 125],
            ],
            [
                'type' => NotificationTypeEnum::ReviewReceived,
                'title' => 'Yeni Rəy Aldınız',
                'content' => 'Xəstəniz sizin haqqınızda 5 ulduzlu rəy yazıb.',
                'icon' => 'star',
                'action_url' => '/reviews/67',
                'action_text' => 'Rəyə bax',
                'data' => ['review_id' => 67, 'rating' => 5],
            ],
            [
                'type' => NotificationTypeEnum::MessageReceived,
                'title' => 'Yeni Mesaj',
                'content' => 'Dr. Nurlan Qəribov sizdən mesaj göndərdi.',
                'icon' => 'message-circle',
                'action_url' => '/messages/89',
                'action_text' => 'Mesajı oxu',
                'data' => ['message_id' => 89],
            ],
            [
                'type' => NotificationTypeEnum::System,
                'title' => 'Sistem Yeniləməsi',
                'content' => 'Doctap.az platforması yeni funksiyalarla yeniləndi.',
                'icon' => 'info',
                'action_url' => '/updates',
                'action_text' => 'Yenilikləri gör',
                'data' => ['version' => '2.5.0'],
            ],
        ];

        $totalNotifications = 0;

        foreach ($users as $user) {
            // Hər istifadəçiyə 3-7 notification əlavə et
            $notificationCount = rand(3, 7);

            for ($i = 0; $i < $notificationCount; $i++) {
                $data = $notificationData[array_rand($notificationData)];

                Notification::create([
                    'uuid' => Str::uuid(),
                    'type' => $data['type'],
                    'user_id' => $user->id,
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'icon' => $data['icon'],
                    'action_url' => $data['action_url'],
                    'action_text' => $data['action_text'],
                    'data' => $data['data'],
                    'read_at' => rand(0, 10) > 6 ? now()->subDays(rand(1, 5)) : null, // 30% oxunmuş
                    'send_at' => now()->subDays(rand(0, 10)),
                    'is_sent' => true,
                    'created_at' => now()->subDays(rand(0, 30)),
                ]);

                $totalNotifications++;
            }
        }

        $this->command->info('✅ Sample notifications yaradıldı (' . $totalNotifications . ' ədəd).');
    }

    /**
     * Notification logs yaradır
     */
    private function createNotificationLogs(): void
    {
        $users = User::query()
            ->where('is_system', false)
            ->get();
        $templates = NotificationTemplate::all();

        $channels = [
            NotificationChannelEnum::Email,
            NotificationChannelEnum::Sms,
            NotificationChannelEnum::Push,
            NotificationChannelEnum::InApp,
        ];

        $errorMessages = [
            'Email server unavailable',
            'Invalid email address',
            'SMS gateway timeout',
            'Device token expired',
            'Push notification service error',
            'Rate limit exceeded',
            'Invalid phone number format',
            'User not found',
        ];

        $sampleContent = [
            'email' => [
                'Hörmətli Dr. Əli Məmmədov, Azad Quliyev xəstəniz sizin haqqınızda 5 ulduzlu rəy yazıb.',
                'Hörmətli Leyla Həsənova, 16 Dekabr 2024 tarixində saat 10:30-da Dr. Rəşad Əliyev həkimi ilə randevunuz təsdiqləndi.',
                'Doctap.az xatırlatması: Sabah saat 14:00-da Dr. Nurlan Qasımov həkimi ilə randevunuz var.',
            ],
            'sms' => [
                'Doctap.az: 16.12.2024 10:30 randevunuz təsdiqləndi. Dr. Əli Məmmədov - MediCenter. 30 dəq əvvəl gəlin.',
                'Doctap.az xatırlatması: Sabah 14:00-da Dr. Leyla Həsənova həkimi ilə randevunuz var. Dent Klinika',
                'Doctap.az: Randevunuz ləğv edildi. Yenisini təyin etmək üçün: 012-123-45-67',
            ],
            'push' => [
                'Dr. Rəşad Əliyev həkimi ilə randevunuz 16 Dekabr tarixinə təyin edildi.',
                'Azad Quliyev sizin haqqınızda rəy yazıb.',
                '24 saat sonra Dr. Nurlan Qasımov həkimi ilə randevunuz var.',
            ],
            'in_app' => [
                'Profiliniz uğurla yeniləndi.',
                'Yeni mesajınız var.',
                'Hesabınız təsdiqləndi.',
            ],
        ];

        $totalLogs = 0;

        foreach ($users as $user) {
            // Hər istifadəçi üçün 10-25 log yarad
            $logCount = rand(10, 25);

            for ($i = 0; $i < $logCount; $i++) {
                $channel = $channels[array_rand($channels)];
                $template = $templates->random();
                $isSuccessful = rand(0, 10) > 1; // 90% uğurlu

                // Recipient yarad
                $recipient = $this->generateRecipient($user, $channel);

                // Content seç
                $content = $sampleContent[$channel][array_rand($sampleContent[$channel])];

                // Subject (yalnız email üçün)
                $subject = $channel === NotificationChannelEnum::Email ?
                    $this->generateEmailSubject($template->type) : null;

                NotificationLog::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'recipient' => $recipient,
                    'notification_type' => $this->getRandomNotificationType(),
                    'template_code' => $template->code,
                    'subject' => $subject,
                    'content' => $content,
                    'is_successful' => $isSuccessful,
                    'error_message' => $isSuccessful ? null : $errorMessages[array_rand($errorMessages)],
                    'sent_at' => now()->subDays(rand(0, 60))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                    'created_at' => now()->subDays(rand(0, 60)),
                ]);

                $totalLogs++;
            }
        }

        $this->command->info('✅ Notification logs yaradıldı (' . $totalLogs . ' ədəd).');
    }

    /**
     * Kanala uyğun recipient yaradır
     */
    private function generateRecipient(User $user, string $channel): string
    {
        switch ($channel) {
            case NotificationChannelEnum::Email:
                return $user->email;

            case NotificationChannelEnum::Sms:
                return $user->phone ?? '+994' . rand(50, 99) . rand(1000000, 9999999);

            case NotificationChannelEnum::Push:
                $devices = NotificationDevice::where('user_id', $user->id)->get();
                if ($devices->count() > 0) {
                    return $devices->random()->device_token;
                }
                return $this->generateDeviceToken(NotificationDeviceTypeEnum::Android);

            case NotificationChannelEnum::InApp:
                return 'user_' . $user->id;

            default:
                return $user->email;
        }
    }

    /**
     * Template tipinə uyğun email subject yaradır
     */
    private function generateEmailSubject(string $templateType): string
    {
        $subjects = [
            NotificationTemplateTypeEnum::Appointment => [
                'Yeni Randevu Yaradıldı - MediCenter',
                'Randevunuz Təsdiqləndi - Dent Klinika',
                'Randevu Xatırlatması - CardioCenter',
                'Randevunuz Ləğv Edildi - EyeClinic',
            ],
            NotificationTemplateTypeEnum::Review => [
                'Yeni Rəy Aldınız - 5 ulduz',
                'Xəstəniz sizin haqqınızda rəy yazıb',
                'Rəyinizə cavab verildi',
            ],
            NotificationTemplateTypeEnum::Message => [
                'Yeni Mesaj - Dr. Əli Məmmədov',
                'Həkiminiz sizə mesaj göndərdi',
                'Cavab gözləyən mesajınız var',
            ],
            NotificationTemplateTypeEnum::System => [
                'Doctap.az-a Xoş Gəlmisiniz!',
                'Hesabınız Təsdiqləndi',
                'Şifrəniz Dəyişdirildi',
                'Sistem Yeniləməsi',
            ],
            NotificationTemplateTypeEnum::User => [
                'Profiliniz Yeniləndi',
                'Yeni Həkim Əlavə Edildi',
                'Favori Həkiminiz Yenilik Paylaşdı',
            ],
        ];

        return $subjects[$templateType][array_rand($subjects[$templateType])];
    }

    /**
     * Təsadüfi notification type qaytarır
     */
    private function getRandomNotificationType(): string
    {
        $types = [
            NotificationTypeEnum::AppointmentCreated,
            NotificationTypeEnum::AppointmentConfirmed,
            NotificationTypeEnum::AppointmentCancelled,
            NotificationTypeEnum::AppointmentReminder,
            NotificationTypeEnum::ReviewReceived,
            NotificationTypeEnum::ReviewResponse,
            NotificationTypeEnum::MessageReceived,
            NotificationTypeEnum::System,
        ];

        return $types[array_rand($types)];
    }

    /**
     * Cihaz növünə görə token yaradır
     */
    private function generateDeviceToken(string $deviceType): string
    {
        switch ($deviceType) {
            case NotificationDeviceTypeEnum::Ios:
                return Str::random(64); // iOS APNS token
            case NotificationDeviceTypeEnum::Android:
                return 'fcm_' . Str::random(152); // FCM token
            case NotificationDeviceTypeEnum::Web:
                return 'web_' . Str::random(88); // Web push token
            default:
                return Str::random(64);
        }
    }

    /**
     * Təsadüfi app versiyası qaytarır
     */
    private function getRandomAppVersion(): string
    {
        $versions = ['2.1.0', '2.2.0', '2.3.0', '2.4.0', '2.5.0'];
        return $versions[array_rand($versions)];
    }
}
