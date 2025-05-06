<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class BlueprintProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blueprint::macro('photo', function (string $field = 'photo') {
            // field_path formatında sütun yaradır
            $columnName = $field . '_path';
            $this->string($columnName)->nullable();
        });

        Blueprint::macro('slug', function () {
            $this->string('slug')->unique();
            $this->index('slug');
        });

        Blueprint::macro('photos', function (string $field = 'galleries') {
            $this->json($field)->nullable();
        });

        Blueprint::macro('translates', function () {
            /*
             * {
             * az: {name: ''},
             * en: {name: ''},
             * ru: {name: ''},
             * }
             * */
            $this->json('translates');
        });

        Blueprint::macro('key', function () {
            $this->uuid('uuid')->unique();
            $this->index('uuid');
        });

        Blueprint::macro('code', function () {
            $this->string('code')->unique();
            $this->index('code');
        });

        Blueprint::macro('order', function () {
            $this->integer('order')->default('0');
            $this->index('order');
        });

        Blueprint::macro('status', function () {
            $this->boolean('is_active')->default(true);
            $this->index('is_active');
        });

        Blueprint::macro('seo', function () {
            $this->json('meta_tags')->nullable();
        });

        Blueprint::macro('customField', function () {
            $this->json('custom_fields')->nullable();
        });

        Blueprint::macro('parentId', function () {
            $table = $this->getTable();

            $this->unsignedBigInteger('parent_id')
                ->default(0)
                ->nullable();

            $this->foreign('parent_id')
                ->references('id')
                ->on($table)
                ->cascadeOnDelete();
        });

        Blueprint::macro('trackable', function () {
            // ID referansı üçün
            $this->unsignedBigInteger('created_by')->nullable();
            $this->unsignedBigInteger('updated_by')->nullable();

            // Audit üçün ad saxlama
            $this->string('created_by_name')->nullable();
            $this->string('updated_by_name')->nullable();

            // Foreign keys
            $this->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $this->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
}
