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
            $this->seedMailSettings();
            $this->seedSocialMedia();
            $this->seedSeoSettings();
            $this->seedUploadSettings();
            $this->seedSecuritySettings();
            $this->seedSystemSettings();
            $this->seedSocialPageSettings();
            $this->seedStorySettings();
            $this->seedListingTitleSettings();
            $this->seedReferralSettings();

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
            'logo' => 'logo.svg',
            'logo_dark' => 'logo.svg',
            'mobile_logo' => 'mobile_logo.svg',
            'mobile_logo_dark' => 'mobile_logo.svg',
            'favicon' => 'favicon.png',
            'wallpaper' => 'wallpaper.png',
            'watermark' => 'watermark.png',
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
            ]
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

    private function seedStorySettings(): void
    {
        $this->createSetting('story', [
            'day_limit' => [
                'regular_user' => 3, // Adi istifadəçi üçün günlük 3 story
                'store' => 10 // Mağaza üçün günlük 10 story
            ],
            'image_limit' => [
                'regular_user' => 5, // Adi istifadəçi üçün bir story-də 5 şəkil
                'store' => 15 // Mağaza üçün bir story-də 15 şəkil
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

    private function seedListingTitleSettings(): void
    {
        $this->createSetting('listing', [
            'pattern' => ':category :attributes :price, :city :region',

            // Atributlar üçün
            'attribute_settings' => [
                'use_only_display_everywhere' => true,
                'separator' => ', '
            ],

            'cache_settings' => [
                'enabled' => true,  // cache aktivdir/deyil
                'ttl' => 3600,  // saniyə ilə (1 saat)
                'prefix' => 'listing_title:'  // cache key prefix
            ],

            // Qiymət formatı
            'price_settings' => [
                'pattern' => ':price :currency'
            ],

            // Ümumi formatlaşdırma
            'formatting' => [
                'max_length' => 100,
                'capitalize_words' => false
            ],
        ]);
    }

    private function seedReferralSettings(): void
    {
        $this->createSetting('referral', [
            // Əsas bonus məbləğləri
            'rewards' => [
                'referrer_amount' => 10.00,    // Dəvət edən üçün,
                'referee_amount' => 5.00,      // Dəvət olunan üçün
            ],

            // Elan sayına görə əlavə bonuslar
            'listing_rewards' => [
                'limit3' => 5.00,    // 3 elan = 5 AZN
                'limit5' => 10.00,   // 5 elan = 10 AZN
                'limit10' => 25.00   // 10 elan = 25 AZN
            ],

            // Sistem parametrləri
            'system' => [
                'link' => '/register',         // Qeydiyyatda keçmək üçün link?
                'auto_generate_code' => true,  // Qeydiyyatda avtomatik kod yaradılsın?
                'code_uppercase' => true,      // Referral kod böyük hərflərlər olsun?
                'code_length' => 8,            // Referral kodun uzunluğu
                'expire_days' => 30,           // Referral linkin etibarlılıq müddəti
                'max_referrals_per_day' => 10  // Gündəlik maksimum dəvət sayı
            ],
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
