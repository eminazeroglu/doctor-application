<?php /** @noinspection PhpUndefinedMethodInspection */

use App\Enums\NotificationPriorityEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\NotificationStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->key(); // UUID makrosu

            // Polymorphic əlaqə üçün sütunlar - notification-ı hansı model alır
            $table->morphs('notifiable');

            // Notification-ın tipi - enum kimi istifadə edəcəyik
            $table->string('type');

            // Notification məlumatları - çevik struktur üçün JSON
            $table->json('data');

            // Notification statusu - oxunub/oxunmayıb
            $table->timestamp('read_at')->nullable();

            // Scheduled notifications üçün
            $table->timestamp('send_at')->nullable();

            // Əhəmiyyət dərəcəsi
            $table->string('priority')->default(NotificationPriorityEnum::NORMAL); // low, normal, high

            // Ümumi status
            $table->string('status')->default(NotificationStatusEnum::PENDING);

            // Kim tərəfindən yaradılıb/yenilənib
            $table->trackable();

            // Soft delete və timestamps
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            // Hər delivery bir notification-a bağlıdır
            $table->foreignId('notification_id')
                ->constrained()
                ->cascadeOnDelete();

            // Hansı kanal ilə göndərilib (email, telegram, push və s.)
            $table->string('channel');

            // Göndərmə uğurlu olub ya yox
            $table->boolean('success');

            // Xəta baş verdikdə səbəbi
            $table->text('error')->nullable();

            // Göndərilmə vaxtı
            $table->timestamp('delivered_at');

            // Created/Updated timestamps
            $table->timestamps();

            // Bir notification eyni kanalla təkrar göndərilməsin
            $table->unique(['notification_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_deliveries');
    }
};
