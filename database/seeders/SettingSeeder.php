<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Setting::truncate();
        Schema::enableForeignKeyConstraints();

        // Now let's seed each setting group one by one
        try {
            $this->seedSiteInfo();
            $this->seedHomePageStatistic();
            $this->seedMailSettings();
            $this->seedSocialMedia();
            $this->seedSeoSettings();
            $this->seedUploadSettings();
            $this->seedSecuritySettings();
            $this->seedSystemSettings();
            $this->seedSocialPageSettings();

            artisan::call('repo:clear');
        }
        catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Seeds basic site information settings
     */
    private function seedSiteInfo(): void
    {
        $this->createSetting('info', [
            "translates" => [
                'az' => [
                    'name' => 'Mənim Saytım',
                    'description' => 'Sayt haqqında məlumat',
                    'address' => 'Bakı şəhəri',
                ],
                'en' => [
                    'name' => 'My Website',
                    'description' => 'About the website',
                    'address' => 'Baku city',
                ]
            ],
            'front_url' => 'https://doctap.az/az',
            'logo' => 'logo.svg',
            'logo_dark' => 'logo.svg',
            'mobile_logo' => 'mobile_logo.svg',
            'mobile_logo_dark' => 'mobile_logo.svg',
            'favicon' => 'favicon.png',
            'wallpaper' => 'wallpaper.png',
            'watermark' => 'watermark.png',
            'join_us_wallpaper' => 'join_us_wallpaper.jpg',
            'app_qr' => 'app_qr.jpg',
            'default_image' => 'default_image.png',
            'email' => 'info@example.com',
            'phone' => [
                [
                    'number' => '+994501234567',
                    'is_whatsapp' => true,
                ]
            ],
            'working_hours' => [
                'monday' => '19:00 - 18:00',
                'tuesday' => '09:00 - 18:00',
                'wednesday' => '09:00 - 18:00',
                'thursday' => '09:00 - 18:00',
                'friday' => '09:00 - 18:00',
                'saturday' => '09:00 - 18:00',
                'sunday' => '09:00 - 18:00',
            ],
            'google_app_link' => 'https://www.google.com/',
            'apple_app_link' => 'https://www.apple.com/',
        ]);
    }

    /**
     * Seeds email configuration settings
     */
    private function seedMailSettings(): void
    {
        $this->createSetting('mail', [
            'driver' => env('MAIL_MAILER', 'smtp'),
            'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'from_address' => env('MAIL_FROM_ADDRESS'),
            'from_name' => env('MAIL_FROM_NAME')
        ]);
    }

    private function seedHomePageStatistic(): void
    {
        $this->createSetting('homeStatistic', [
            'patient_count' => 23643,
            'doctor_count' => 6475,
            'practicing_doctor_count' => 1482,
            'clinic_count' => 560,
        ]);
    }

    /**
     * Seeds social media related settings
     */
    private function seedSocialMedia(): void
    {
        $this->createSetting('socialMedia', [
            'facebook' => [
                'url' => 'https://facebook.com/mywebsite',
                'icon' => 'fab fa-facebook',
                'active' => true
            ],
            'instagram' => [
                'url' => 'https://instagram.com/mywebsite',
                'icon' => 'fab fa-instagram',
                'active' => true
            ],
            'twitter' => [
                'url' => 'https://twitter.com/mywebsite',
                'icon' => 'fab fa-twitter',
                'active' => true
            ],
            'linkedin' => [
                'url' => 'https://linkedin.com/company/mywebsite',
                'icon' => 'fab fa-linkedin',
                'active' => true
            ],
            'youtube' => [
                'url' => 'https://youtube.com/mywebsite',
                'icon' => 'fab fa-youtube',
                'active' => true
            ],
            'tiktok' => [
                'url' => 'https://tiktok.com/@mywebsite',
                'icon' => 'fab fa-tiktok',
                'active' => true
            ],
            'telegram' => [
                'url' => 'https://t.me/mywebsite',
                'icon' => 'fab fa-telegram',
                'active' => true
            ]
        ]);
    }

    /**
     * Seeds SEO and analytics related settings
     */
    private function seedSeoSettings(): void
    {
        $this->createSetting('seo', [
            'google' => [
                'analytics_id' => 'UA-123456-1', // Analytics
                'tag_manager' => 'GTM-ABCDEF',   // Tag Manager
                'search_console_key' => 'google-site-verification=abc123', // Search Console
                'ads_conversion_id' => 'AW-987654321', // Ads Conversion
                'map_key' => 'mapkey', // Ads Conversion
            ],
            'yandex' => [
                'metrika_id' => '123456', // Yandex Metrika sayğac ID
                'verification_key' => 'yandex_verification_123', // Yandex meta təsdiq kodu
                'webmaster_code' => 'yandex_webmaster_456', // Yandex Webmaster kodu
                'metrika_webvisor' => true, // Webvizor aktiv
                'metrika_ecommerce' => 'YM-12345-ECOM' // E-commerce tracking kodu
            ],
            'facebook' => [
                'pixel' => '123456789012345', // Facebook Pixel ID
                'app_id' => '987654321098765', // Facebook App ID
                'verification_key' => 'facebook_meta_789' // Facebook meta təsdiq kodu
            ],
            'robots_txt' => "User-agent: *\nDisallow: /admin/\nDisallow: /api/",
            'sitemap_settings' => [
                'auto_generate' => true,
                'frequency' => 'weekly',
                'priority' => '0.8'
            ]
        ]);
    }

    /**
     * Seeds file upload and media related settings
     */
    private function seedUploadSettings(): void
    {
        $this->createSetting('upload', [
            'max_file_size' => 10240, // 10MB in KB
            'max_image_size' => 10240, // 10MB in KB
            'allowed_file_types' => [
                'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
                'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            ],
            'image_quality' => 85,
            'image_sizes' => [
                'thumbnail' => ['width' => 150, 'height' => 150],
                'medium' => ['width' => 300, 'height' => 300],
                'large' => ['width' => 800, 'height' => 800]
            ],
            'watermark' => [
                'enabled' => false,
                'position' => 'bottom-right',
                'opacity' => 50
            ],
            'storage_driver' => 'local'
        ]);
    }

    /**
     * Seeds security related settings
     */
    private function seedSecuritySettings(): void
    {
        $this->createSetting('security', [
            'max_login_attempts' => 5,
            'login_lockout_time' => 15, // minutes
            'password_policy' => [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_numeric' => true,
                'require_special_chars' => true
            ],
            'api_rate_limit' => [
                'enabled' => true,
                'max_attempts' => 60,
                'decay_minutes' => 1
            ],
            'recaptcha' => [
                'enabled' => false,
                'site_key' => '',
                'secret_key' => ''
            ]
        ]);
    }

    /**
     * Seeds system level settings
     */
    private function seedSystemSettings(): void
    {
        $this->createSetting('system', [
            'environment' => 'development',
            'debug_mode' => true,
            'timezone' => 'Asia/Baku',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'default_language' => 'az',
            'cache' => [
                'enabled' => true,
                'ttl' => 3600, // 1 hour in seconds
                'driver' => env('CACHE_DRIVER'),
                'prefix' => env('CACHE_PREFIX')
            ],
            'queue' => [
                'default' => 'sync',
                'failed_job_retention_days' => 30
            ],
            'backup' => [
                'enabled' => true,
                'frequency' => 'daily',
                'retention_days' => 7
            ]
        ]);
    }

    /**
     * Seeds file upload and media related settings
     */
    private function seedSocialPageSettings(): void
    {
        $this->createSetting('socialAuth', [
            'telegram' => [
                'token' => "8039241700:AAHgJcRqj7mZcerq1e4Wfq4wVFk2I0i6Cls",
                'webhook_url' => url('api/telegram/webhook'),
                'chat_id' => "547360436",
                'is_active' => true
            ],
            'google' => [
                'client_id' => 'demo-your-google-client-id',
                'client_secret' => 'your-google-client-secret',
                'redirect' => 'https://your-domain.com/api/auth/callback/google',
                'is_active' => true
            ],

            'facebook' => [
                'client_id' => 'your-facebook-client-id',
                'client_secret' => 'your-facebook-client-secret',
                'redirect' => 'https://your-domain.com/api/auth/callback/facebook',
                'is_active' => true
            ],

            'linkedin' => [
                'client_id' => 'your-linkedin-client-id',
                'client_secret' => 'your-linkedin-client-secret',
                'redirect' => 'https://your-domain.com/api/auth/callback/linkedin',
                'is_active' => true
            ]
        ]);
    }

    /**
     * Helper method to create a setting
     */
    private function createSetting(string $key, array $values): void
    {
        Setting::create([
            'key' => $key,
            'values' => $values
        ]);
    }
}
