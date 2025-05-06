<?php

use App\Enums\UserStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function createUserTable()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->key();
            $table->photo();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('code');
            $table->string('username');
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->decimal('main_balance', 10, 2)->default(0);
            $table->decimal('referral_balance', 10, 2)->default(0);
            $table->string('referral_code')->nullable()->unique();
            $table->enum('gender', \App\Enums\GenderEnum::getValues())->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('status')->default(UserStatusEnum::PendingMail);
            $table->json('social_links')->nullable();
            $table->boolean('is_system')->default(false);
            $table->customField();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function createUserLoginHistoryTable(): void
    {
        Schema::create('user_login_history', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address'); // Giriş IP ünvanı
            $table->string('user_agent'); // Brauzer və cihaz məlumatları
            $table->string('location')->nullable(); // Giriş edilən yer
            $table->string('device_type')->nullable(); // İstifadə edilən cihaz növü
            $table->json('meta_data')->nullable(); // İstifadəçinin məlumatları
            $table->timestamp('logged_in_at'); // Giriş tarixi
            $table->timestamp('logged_out_at')->nullable(); // Çıxış tarixi
        });
    }

    public function createUserPreferencesTable(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();                    // Avtomatik artan unikal identifikator
            $table->key();
            $table->unsignedBigInteger('user_id')->unique();  // İstifadəçinin unikal ID-si
            $table->boolean('dark_mode')->default(false);     // Tünd rejim aktiv/deaktiv
            $table->string('language')->default('az');        // İnterfeys dili
            /*
             * {
              "email_notifications": true,
              "push_notifications": false,
              "new_article_alert": true,
              "comment_replies": true
            }
             * */
            $table->json('notification_settings')->nullable(); // Bildiriş tənzimləmələri
            /*
             * {
              "favorite_categories": ["technology", "science", "politics"],
              "excluded_topics": ["sports", "celebrity"]
            }
             * */
            $table->json('content_preferences')->nullable();   // Məzmun üstünlükləri
            $table->string('timezone')->default('UTC');        // İstifadəçinin saat qurşağı
            $table->enum('email_frequency', ['daily', 'weekly', 'monthly', 'never'])->default('weekly'); // E-poçt bildirişlərinin tezliyi
            $table->boolean('show_email')->default(false);     // E-poçt ünvanının görünüb-görünməməsi
            $table->boolean('show_profile_views')->default(true); // Profil baxış sayının görünüb-görünməməsi
            /*
             {
              "profile_visibility": "public",
              "show_online_status": true,
              "allow_messages_from": "followers"
             }
             * */
            $table->json('privacy_settings')->nullable();      // Əlavə gizlilik tənzimləmələri
            $table->timestamps();            // Yaradılma və yenilənmə tarixləri

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function createUserSocialLoginTable(): void
    {
        Schema::create('user_social_logins', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // İstifadəçi ilə əlaqə
            $table->string('provider'); // Sosial platform (Google, LinkedIn və s.)
            $table->string('provider_id'); // Platformadan gələn ID
            $table->json('provider_data')->nullable(); // Platformadan gələn əlavə məlumatlar
            $table->timestamps();
        });
    }

    public function up(): void
    {
        $this->createUserTable();
        $this->createUserLoginHistoryTable();
        $this->createUserPreferencesTable();
        $this->createUserSocialLoginTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('user_login_history');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_social_logins');
    }
};
