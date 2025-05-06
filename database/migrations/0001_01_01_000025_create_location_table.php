<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function createCountry(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->slug();
            $table->translates();
            $table->string('phone_code')->nullable();
            $table->string('currency')->nullable();
            /*
             * latitude: '',
             * longitude: '',
             * */
            $table->json('map_location')->nullable();
            $table->order();
            $table->status();
            $table->timestamps();
        });
    }

    public function createCity(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->slug();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->translates();
            /*
             * latitude: '',
             * longitude: '',
             * */
            $table->json('map_location')->nullable();
            $table->order();
            $table->status();
            $table->timestamps();
        });
    }

    public function createRegion(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->slug();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->translates();
            /*
             * latitude: '',
             * longitude: '',
             * */
            $table->json('map_location')->nullable();
            $table->order();
            $table->status();
            $table->timestamps();
        });
    }

    public function createSubway(): void
    {
        Schema::create('subways', function (Blueprint $table) {
            $table->id();
            $table->key();
            $table->slug();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->translates();
            /*
             * latitude: '',
             * longitude: '',
             * */
            $table->json('map_location')->nullable();
            $table->order();
            $table->status();
            $table->timestamps();
        });
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createCountry();
        $this->createCity();
        $this->createRegion();
        $this->createSubway();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('subways');
    }
};
