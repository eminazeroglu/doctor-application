<?php

namespace Database\Seeders;

use App\Enums\AttributePositionEnum;
use App\Enums\AttributeTypeEnum;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Attribute::truncate();
        AttributeOption::truncate();

        // Həkimlərin əsas atributları
        $this->createDoctorBasicAttributes();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Həkimlərin əsas atributları
     */
    private function createDoctorBasicAttributes(): void
    {
        // Danışdığı dillər
        $languages = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Danışdığı dillər', 'description' => 'Həkimin danışdığı dillər'],
                'en' => ['name' => 'Languages spoken', 'description' => 'Languages spoken by the doctor']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 5
        ]);

        // Dil seçimləri
        $this->createAttributeOptions($languages->id, [
            ['translates' => ['az' => ['name' => 'Azərbaycan dili'], 'en' => ['name' => 'Azerbaijani']]],
            ['translates' => ['az' => ['name' => 'Rus dili'], 'en' => ['name' => 'Russian']]],
            ['translates' => ['az' => ['name' => 'İngilis dili'], 'en' => ['name' => 'English']]],
            ['translates' => ['az' => ['name' => 'Türk dili'], 'en' => ['name' => 'Turkish']]],
            ['translates' => ['az' => ['name' => 'Ərəb dili'], 'en' => ['name' => 'Arabic']]],
            ['translates' => ['az' => ['name' => 'Fars dili'], 'en' => ['name' => 'Persian']]],
            ['translates' => ['az' => ['name' => 'Alman dili'], 'en' => ['name' => 'German']]],
            ['translates' => ['az' => ['name' => 'Fransız dili'], 'en' => ['name' => 'French']]]
        ]);

        // Yaş həddi
        $ageGroup = $this->createAttribute([
            'translates' => [
                'az' => ['name' => 'Yaş həddi', 'description' => 'Həkimin xidmət göstərdiyi yaş qrupları'],
                'en' => ['name' => 'Age group', 'description' => 'Age groups served by the doctor']
            ],
            'type' => AttributeTypeEnum::MultiSelect,
            'order' => 6
        ]);

        // Yaş qrupu seçimləri
        $this->createAttributeOptions($ageGroup->id, [
            ['translates' => ['az' => ['name' => 'Körpələr (0-1 yaş)'], 'en' => ['name' => 'Infants (0-1 years)']]],
            ['translates' => ['az' => ['name' => 'Uşaqlar (1-12 yaş)'], 'en' => ['name' => 'Children (1-12 years)']]],
            ['translates' => ['az' => ['name' => 'Yeniyetmələr (12-18 yaş)'], 'en' => ['name' => 'Teenagers (12-18 years)']]],
            ['translates' => ['az' => ['name' => 'Böyüklər (18-65 yaş)'], 'en' => ['name' => 'Adults (18-65 years)']]],
            ['translates' => ['az' => ['name' => 'Yaşlılar (65+ yaş)'], 'en' => ['name' => 'Elderly (65+ years)']]]
        ]);
    }

    /**
     * Ana attribute yaradır
     */
    private function createAttribute(array $data): Attribute
    {
        $faker = \Faker\Factory::create();
        return Attribute::create([
            ...$data,
            'group_name' => $faker->word
        ]);
    }

    /**
     * Attribute üçün options yaradır
     */
    private function createAttributeOptions(int $attributeId, array $options): void
    {
        foreach ($options as $index => $option) {
            $lastOrder = Attribute::find($attributeId)->options()->max('order') ?? 0;
            Attribute::find($attributeId)->options()->create([
                ...$option,
                'order' => $lastOrder + 1,
                'is_active' => true
            ]);
        }
    }
}
