<?php

namespace Database\Seeders;

use App\Enums\PageTypeEnum;
use App\Enums\WidgetTypeEnum;
use App\Models\Page;
use App\Models\PageWidget;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Məxfilik siyasəti səhifəsi
        $privacyPage = Page::updateOrCreate(
            ['slug' => 'mexfilik-siyaseti'],
            [
                'type' => PageTypeEnum::POLICY,
                'is_system' => true,
                'is_active' => true,
                'translates' => [
                    'az' => [
                        'name' => 'Məxfilik siyasəti',
                        'content' => 'Məxfilik siyasəti məzmunu burada yerləşir...'
                    ],
                    'en' => [
                        'name' => 'Privacy Policy',
                        'content' => 'Privacy policy content goes here...'
                    ],
                    'ru' => [
                        'name' => 'Политика конфиденциальности',
                        'content' => 'Содержание политики конфиденциальности размещается здесь...'
                    ]
                ]
            ]
        );

        // 2. Qaydalar səhifəsi
        $termsPage = Page::updateOrCreate(
            ['slug' => 'qaydalar'],
            [
                'type' => PageTypeEnum::TERMS,
                'is_system' => true,
                'is_active' => true,
                'translates' => [
                    'az' => [
                        'name' => 'Qaydalar',
                        'content' => 'Qaydalar məzmunu burada yerləşir...'
                    ],
                    'en' => [
                        'name' => 'Terms',
                        'content' => 'Terms content goes here...'
                    ],
                    'ru' => [
                        'name' => 'Правила',
                        'content' => 'Содержание правил размещается здесь...'
                    ]
                ]
            ]
        );

        // 3. Haqqımızda səhifəsi və widget-ları
        $aboutPage = Page::updateOrCreate(
            ['slug' => 'haqqimizda'],
            [
                'type' => PageTypeEnum::ABOUT,
                'is_system' => true,
                'is_active' => true,
                'translates' => [
                    'az' => [
                        'name' => 'Haqqımızda',
                        'content' => 'Azərbaycanın ən böyük elanlar platforması ELANYERİ.AZ

Azərbaycan qanunvericiliyinə uyğun olaraq qeydiyyatdan keçmiş və fəaliyyət göstərən, ElanYeri.Az resursu üzərində bütün müstəsna mülkiyyət hüquqlarına sahib olan "ELANYERİ" Məhdud Məsuliyyətli Cəmiyyəti.'
                    ],
                    'en' => [
                        'name' => 'About Us',
                        'content' => 'Azerbaijan\'s largest classified ads platform ELANYERİ.AZ

Registered and operating in accordance with the legislation of Azerbaijan, "ELANYERİ" Limited Liability Company, which owns all exclusive property rights over the ElanYeri.Az resource.'
                    ],
                    'ru' => [
                        'name' => 'О нас',
                        'content' => 'Крупнейшая платформа объявлений в Азербайджане ELANYERİ.AZ

Зарегистрированное и действующее в соответствии с законодательством Азербайджана Общество с ограниченной ответственностью "ELANYERİ", которому принадлежат все исключительные имущественные права на ресурс ElanYeri.Az.'
                    ]
                ]
            ]
        );

        // Haqqımızda səhifəsi üçün widget-ları yaratmaq
        $aboutWidgets = [
            [
                'page_id' => $aboutPage->id,
                'type' => WidgetTypeEnum::ICON_BOX,
                'order' => 1,
                'is_active' => true,
                'data' => [
                    'icon' => 'star',
                    'background' => '#FFFFFF'
                ],
                'translates' => [
                    'az' => [
                        'title' => 'Azərbaycanın ən böyük elan platforması',
                        'description' => 'Azərbaycan qanunvericiliyinə uyğun olaraq qeydiyyatdan keçmiş və fəaliyyət göstərən'
                    ],
                    'en' => [
                        'title' => 'Azerbaijan\'s largest ads platform',
                        'description' => 'Registered and operating in accordance with the legislation of Azerbaijan'
                    ],
                    'ru' => [
                        'title' => 'Крупнейшая платформа объявлений в Азербайджане',
                        'description' => 'Зарегистрированное и действующее в соответствии с законодательством Азербайджана'
                    ]
                ]
            ],
            [
                'page_id' => $aboutPage->id,
                'type' => WidgetTypeEnum::ICON_BOX,
                'order' => 2,
                'is_active' => true,
                'data' => [
                    'icon' => 'grid',
                    'background' => '#FFFFFF'
                ],
                'translates' => [
                    'az' => [
                        'title' => 'Axtardığın hər növ elanlar toplusu',
                        'description' => 'Azərbaycan qanunvericiliyinə uyğun olaraq qeydiyyatdan keçmiş və fəaliyyət göstərən'
                    ],
                    'en' => [
                        'title' => 'Collection of all types of ads you are looking for',
                        'description' => 'Registered and operating in accordance with the legislation of Azerbaijan'
                    ],
                    'ru' => [
                        'title' => 'Собрание всех видов объявлений, которые вы ищете',
                        'description' => 'Зарегистрированное и действующее в соответствии с законодательством Азербайджана'
                    ]
                ]
            ],
            [
                'page_id' => $aboutPage->id,
                'type' => WidgetTypeEnum::ICON_BOX,
                'order' => 3,
                'is_active' => true,
                'data' => [
                    'icon' => 'check-circle',
                    'background' => '#FFFFFF'
                ],
                'translates' => [
                    'az' => [
                        'title' => 'Ən sərfəli qiymətlə aylıq abunəlik',
                        'description' => 'Azərbaycan qanunvericiliyinə uyğun olaraq qeydiyyatdan keçmiş və fəaliyyət göstərən'
                    ],
                    'en' => [
                        'title' => 'Monthly subscription at the most affordable price',
                        'description' => 'Registered and operating in accordance with the legislation of Azerbaijan'
                    ],
                    'ru' => [
                        'title' => 'Ежемесячная подписка по самой выгодной цене',
                        'description' => 'Зарегистрированное и действующее в соответствии с законодательством Азербайджана'
                    ]
                ]
            ]
        ];

        // Widget-ları yaratmaq və ya mövcud olanları yeniləmək
        foreach ($aboutWidgets as $widgetData) {
            PageWidget::updateOrCreate(
                [
                    'page_id' => $widgetData['page_id'],
                    'order' => $widgetData['order'],
                    'type' => $widgetData['type']
                ],
                $widgetData
            );
        }
    }
}
