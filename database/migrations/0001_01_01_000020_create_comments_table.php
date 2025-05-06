<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->key();

            // Polymorphic əlaqə
            $table->morphs('commentable');  // commentable_id, commentable_type sütunlarını yaradır

            // Əsas məlumatlar
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->onDelete('cascade');

            $table->text('content');

            // Meta məlumatlar
            $table->json('meta_data')->nullable();
            /*
                {
                    "edited": {
                        "is_edited": true,
                        "edited_at": "2024-01-01 10:00:00",
                        "edited_by": 1,
                        "reason": "Yazı xətası"
                    },
                    "attachments": [
                        {
                            "type": "image",
                            "url": "path/to/image.jpg"
                        }
                    ],
                    "reactions": {
                        "like": [1, 2, 3],
                        "dislike": [4, 5]
                    }
                }
            */

            $table->boolean('is_private')->default(false);
            $table->status();

            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
