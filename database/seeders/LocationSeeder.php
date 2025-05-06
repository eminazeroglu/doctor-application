<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Models\Subway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Mövcud cədvəlləri təmizlə
        Region::query()->truncate();
        City::query()->truncate();
        Country::query()->truncate();

        $this->createAzerbaijan();
        $this->createTurkey();
        $this->createGeorgia();
        $this->createRussia();

        Schema::enableForeignKeyConstraints();
    }

    protected function createAzerbaijan(): void
    {
        $azerbaijanData = [
            'translates' => [
                'az' => ['name' => 'Azərbaycan'],
                'en' => ['name' => 'Azerbaijan'],
                'ru' => ['name' => 'Азербайджан'],
            ],
            'phone_code' => '994',
            'currency' => 'AZN',
            'map_location' => ['lat' => '40.143105', 'lng' => '47.576927'],
            'cities' => [
                [
                    'translates' => [
                        'az' => ['name' => 'Bakı'],
                        'en' => ['name' => 'Baku'],
                        'ru' => ['name' => 'Баку'],
                    ],
                    'map_location' => ['lat' => '40.4093', 'lng' => '49.8671'],
                    'regions' => [
                        [
                            'translates' => ['az' => ['name' => 'Binəqədi'], 'en' => ['name' => 'Binagadi'], 'ru' => ['name' => 'Бинагади']],
                            'map_location' => ['lat' => '40.4636', 'lng' => '49.7958'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => 'Azadlıq Prospekti'], 'en' => ['name' => 'Azadliq Prospekti'], 'ru' => ['name' => 'Проспект Азадлыг']], 'map_location' => ['lat' => '40.4253', 'lng' => '49.8420']],
                                ['translates' => ['az' => ['name' => 'Nəsimi'], 'en' => ['name' => 'Nasimi'], 'ru' => ['name' => 'Насими']], 'map_location' => ['lat' => '40.4235', 'lng' => '49.8280']],
                            ],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Qaradağ'], 'en' => ['name' => 'Garadagh'], 'ru' => ['name' => 'Гарадаг']],
                            'map_location' => ['lat' => '40.3167', 'lng' => '49.6333'],
                            'subways' => [],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Xətai'], 'en' => ['name' => 'Khatai'], 'ru' => ['name' => 'Хатаи']],
                            'map_location' => ['lat' => '40.3700', 'lng' => '49.8833'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => 'Xətai'], 'en' => ['name' => 'Khatai'], 'ru' => ['name' => 'Хатаи']], 'map_location' => ['lat' => '40.3705', 'lng' => '49.8945']],
                                ['translates' => ['az' => ['name' => 'Əhmədli'], 'en' => ['name' => 'Ahmadli'], 'ru' => ['name' => 'Ахмедлы']], 'map_location' => ['lat' => '40.3850', 'lng' => '49.9550']],
                                ['translates' => ['az' => ['name' => 'Həzi Aslanov'], 'en' => ['name' => 'Hazi Aslanov'], 'ru' => ['name' => 'Гази Асланов']], 'map_location' => ['lat' => '40.3720', 'lng' => '49.9560']],
                            ],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Xəzər'], 'en' => ['name' => 'Khazar'], 'ru' => ['name' => 'Хазар']],
                            'map_location' => ['lat' => '40.4167', 'lng' => '50.1333'],
                            'subways' => [],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Nərimanov'], 'en' => ['name' => 'Narimanov'], 'ru' => ['name' => 'Нариманов']],
                            'map_location' => ['lat' => '40.4024', 'lng' => '49.8705'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => 'Nəriman Nərimanov'], 'en' => ['name' => 'Nariman Narimanov'], 'ru' => ['name' => 'Нариман Нариманов']], 'map_location' => ['lat' => '40.4020', 'lng' => '49.8710']],
                                ['translates' => ['az' => ['name' => 'Gənclik'], 'en' => ['name' => 'Ganclik'], 'ru' => ['name' => 'Гянджлик']], 'map_location' => ['lat' => '40.4005', 'lng' => '49.8510']],
                            ],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Nəsimi'], 'en' => ['name' => 'Nasimi'], 'ru' => ['name' => 'Насими']],
                            'map_location' => ['lat' => '40.4231', 'lng' => '49.8319'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => '28 May'], 'en' => ['name' => '28 May'], 'ru' => ['name' => '28 Мая']], 'map_location' => ['lat' => '40.3795', 'lng' => '49.8480']],
                                ['translates' => ['az' => ['name' => 'Memar Əcəmi'], 'en' => ['name' => 'Memar Ajami'], 'ru' => ['name' => 'Мемар Аджеми']], 'map_location' => ['lat' => '40.4110', 'lng' => '49.8140']],
                            ],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Nizami'], 'en' => ['name' => 'Nizami'], 'ru' => ['name' => 'Низами']],
                            'map_location' => ['lat' => '40.4090', 'lng' => '49.9180'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => 'Neftçilər'], 'en' => ['name' => 'Neftchilar'], 'ru' => ['name' => 'Нефтчиляр']], 'map_location' => ['lat' => '40.4100', 'lng' => '49.9440']],
                                ['translates' => ['az' => ['name' => 'Qara Qarayev'], 'en' => ['name' => 'Gara Garayev'], 'ru' => ['name' => 'Гара Гараев']], 'map_location' => ['lat' => '40.3950', 'lng' => '49.9330']],
                            ],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Sabunçu'], 'en' => ['name' => 'Sabunchu'], 'ru' => ['name' => 'Сабунчу']],
                            'map_location' => ['lat' => '40.4425', 'lng' => '49.9481'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => 'Koroğlu'], 'en' => ['name' => 'Koroglu'], 'ru' => ['name' => 'Кёроглу']], 'map_location' => ['lat' => '40.4200', 'lng' => '49.9180']],
                                ['translates' => ['az' => ['name' => 'Bakmil'], 'en' => ['name' => 'Bakmil'], 'ru' => ['name' => 'Бакмил']], 'map_location' => ['lat' => '40.4210', 'lng' => '49.8970']],
                            ],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Səbail'], 'en' => ['name' => 'Sabail'], 'ru' => ['name' => 'Сабаил']],
                            'map_location' => ['lat' => '40.3667', 'lng' => '49.8333'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => 'İçərişəhər'], 'en' => ['name' => 'Icherisheher'], 'ru' => ['name' => 'Ичеришехер']], 'map_location' => ['lat' => '40.3660', 'lng' => '49.8310']],
                                ['translates' => ['az' => ['name' => 'Sahil'], 'en' => ['name' => 'Sahil'], 'ru' => ['name' => 'Сахил']], 'map_location' => ['lat' => '40.3740', 'lng' => '49.8450']],
                            ],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Suraxanı'], 'en' => ['name' => 'Surakhani'], 'ru' => ['name' => 'Сураханы']],
                            'map_location' => ['lat' => '40.4210', 'lng' => '49.9860'],
                            'subways' => [],
                        ],
                        [
                            'translates' => ['az' => ['name' => 'Yasamal'], 'en' => ['name' => 'Yasamal'], 'ru' => ['name' => 'Ясамал']],
                            'map_location' => ['lat' => '40.3893', 'lng' => '49.8220'],
                            'subways' => [
                                ['translates' => ['az' => ['name' => 'Elmlər Akademiyası'], 'en' => ['name' => 'Elmlar Akademiyasi'], 'ru' => ['name' => 'Академия Наук']], 'map_location' => ['lat' => '40.3745', 'lng' => '49.8150']],
                                ['translates' => ['az' => ['name' => 'İnşaatçılar'], 'en' => ['name' => 'Inshaatchilar'], 'ru' => ['name' => 'Иншаатчылар']], 'map_location' => ['lat' => '40.3880', 'lng' => '49.8030']],
                                ['translates' => ['az' => ['name' => '20 Yanvar'], 'en' => ['name' => '20 Yanvar'], 'ru' => ['name' => '20 Января']], 'map_location' => ['lat' => '40.3990', 'lng' => '49.8050']],
                            ],
                        ],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Gəncə'],
                        'en' => ['name' => 'Ganja'],
                        'ru' => ['name' => 'Гянджа'],
                    ],
                    'map_location' => ['lat' => '40.6828', 'lng' => '46.3606'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Kəpəz'], 'en' => ['name' => 'Kapaz'], 'ru' => ['name' => 'Кяпаз']], 'map_location' => ['lat' => '40.6820', 'lng' => '46.3570']],
                        ['translates' => ['az' => ['name' => 'Nizami'], 'en' => ['name' => 'Nizami'], 'ru' => ['name' => 'Низами']], 'map_location' => ['lat' => '40.6750', 'lng' => '46.3650']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Sumqayıt'],
                        'en' => ['name' => 'Sumgait'],
                        'ru' => ['name' => 'Сумгаит'],
                    ],
                    'map_location' => ['lat' => '40.5898', 'lng' => '49.6308'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Hacı Zeynalabdin'], 'en' => ['name' => 'Haji Zeynalabdin'], 'ru' => ['name' => 'Хаджи Зейналабдин']], 'map_location' => ['lat' => '40.5760', 'lng' => '49.6360']],
                        ['translates' => ['az' => ['name' => 'Corat'], 'en' => ['name' => 'Jorat'], 'ru' => ['name' => 'Джорат']], 'map_location' => ['lat' => '40.5720', 'lng' => '49.6700']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şirvan'],
                        'en' => ['name' => 'Shirvan'],
                        'ru' => ['name' => 'Ширван'],
                    ],
                    'map_location' => ['lat' => '39.9417', 'lng' => '48.9239'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Mingəçevir'],
                        'en' => ['name' => 'Mingachevir'],
                        'ru' => ['name' => 'Мингечаур'],
                    ],
                    'map_location' => ['lat' => '40.7725', 'lng' => '47.0494'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Abşeron'],
                        'en' => ['name' => 'Absheron'],
                        'ru' => ['name' => 'Апшерон'],
                    ],
                    'map_location' => ['lat' => '40.3630', 'lng' => '49.8271'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Xırdalan'], 'en' => ['name' => 'Khirdalan'], 'ru' => ['name' => 'Хырдалан']], 'map_location' => ['lat' => '40.4486', 'lng' => '49.7550']],
                        ['translates' => ['az' => ['name' => 'Hökməli'], 'en' => ['name' => 'Hokmali'], 'ru' => ['name' => 'Хокмали']], 'map_location' => ['lat' => '40.3830', 'lng' => '49.8200']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ağcabədi'],
                        'en' => ['name' => 'Agjabadi'],
                        'ru' => ['name' => 'Агджабеди'],
                    ],
                    'map_location' => ['lat' => '40.0500', 'lng' => '47.4500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ağdam'],
                        'en' => ['name' => 'Aghdam'],
                        'ru' => ['name' => 'Агдам'],
                    ],
                    'map_location' => ['lat' => '40.0667', 'lng' => '46.9333'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ağdaş'],
                        'en' => ['name' => 'Aghdash'],
                        'ru' => ['name' => 'Агдаш'],
                    ],
                    'map_location' => ['lat' => '40.6333', 'lng' => '47.4667'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ağstafa'],
                        'en' => ['name' => 'Agstafa'],
                        'ru' => ['name' => 'Агстафа'],
                    ],
                    'map_location' => ['lat' => '41.1189', 'lng' => '45.4539'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ağsu'],
                        'en' => ['name' => 'Aghsu'],
                        'ru' => ['name' => 'Агсу'],
                    ],
                    'map_location' => ['lat' => '40.5706', 'lng' => '48.4008'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Astara'],
                        'en' => ['name' => 'Astara'],
                        'ru' => ['name' => 'Астара'],
                    ],
                    'map_location' => ['lat' => '38.4559', 'lng' => '48.8750'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Balakən'],
                        'en' => ['name' => 'Balakan'],
                        'ru' => ['name' => 'Белоканы'],
                    ],
                    'map_location' => ['lat' => '41.7261', 'lng' => '46.4044'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Beyləqan'],
                        'en' => ['name' => 'Beylagan'],
                        'ru' => ['name' => 'Бейлаган'],
                    ],
                    'map_location' => ['lat' => '39.7667', 'lng' => '47.6167'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Biləsuvar'],
                        'en' => ['name' => 'Bilasuvar'],
                        'ru' => ['name' => 'Билясувар'],
                    ],
                    'map_location' => ['lat' => '39.4597', 'lng' => '48.5507'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Cəbrayıl'],
                        'en' => ['name' => 'Jabrayil'],
                        'ru' => ['name' => 'Джебраил'],
                    ],
                    'map_location' => ['lat' => '39.4000', 'lng' => '47.0333'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Cəlilabad'],
                        'en' => ['name' => 'Jalilabad'],
                        'ru' => ['name' => 'Джалилабад'],
                    ],
                    'map_location' => ['lat' => '39.2089', 'lng' => '48.4936'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Daşkəsən'],
                        'en' => ['name' => 'Dashkasan'],
                        'ru' => ['name' => 'Дашкесан'],
                    ],
                    'map_location' => ['lat' => '40.5167', 'lng' => '46.0833'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Füzuli'],
                        'en' => ['name' => 'Fuzuli'],
                        'ru' => ['name' => 'Физули'],
                    ],
                    'map_location' => ['lat' => '39.6000', 'lng' => '47.1500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Gədəbəy'],
                        'en' => ['name' => 'Gadabay'],
                        'ru' => ['name' => 'Гедабек'],
                    ],
                    'map_location' => ['lat' => '40.5667', 'lng' => '45.8167'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Goranboy'],
                        'en' => ['name' => 'Goranboy'],
                        'ru' => ['name' => 'Геранбой'],
                    ],
                    'map_location' => ['lat' => '40.6100', 'lng' => '47.1200'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Göyçay'],
                        'en' => ['name' => 'Goychay'],
                        'ru' => ['name' => 'Гейчай'],
                    ],
                    'map_location' => ['lat' => '40.6500', 'lng' => '47.7500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Göygöl'],
                        'en' => ['name' => 'Goygol'],
                        'ru' => ['name' => 'Гёйгёль'],
                    ],
                    'map_location' => ['lat' => '40.5833', 'lng' => '46.3167'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Hacıqabul'],
                        'en' => ['name' => 'Hajigabul'],
                        'ru' => ['name' => 'Гаджигабул'],
                    ],
                    'map_location' => ['lat' => '40.0667', 'lng' => '48.9333'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'İmişli'],
                        'en' => ['name' => 'Imishli'],
                        'ru' => ['name' => 'Имишли'],
                    ],
                    'map_location' => ['lat' => '39.8667', 'lng' => '48.0667'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'İsmayıllı'],
                        'en' => ['name' => 'Ismayilli'],
                        'ru' => ['name' => 'Исмаиллы'],
                    ],
                    'map_location' => ['lat' => '40.7833', 'lng' => '48.1500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kəlbəcər'],
                        'en' => ['name' => 'Kalbajar'],
                        'ru' => ['name' => 'Кельбаджар'],
                    ],
                    'map_location' => ['lat' => '40.1025', 'lng' => '46.0347'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kürdəmir'],
                        'en' => ['name' => 'Kurdamir'],
                        'ru' => ['name' => 'Кюрдамир'],
                    ],
                    'map_location' => ['lat' => '40.3333', 'lng' => '48.1667'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Laçın'],
                        'en' => ['name' => 'Lachin'],
                        'ru' => ['name' => 'Лачин'],
                    ],
                    'map_location' => ['lat' => '39.6389', 'lng' => '46.5472'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Lənkəran'],
                        'en' => ['name' => 'Lankaran'],
                        'ru' => ['name' => 'Ленкорань'],
                    ],
                    'map_location' => ['lat' => '38.7547', 'lng' => '48.8486'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Lerik'],
                        'en' => ['name' => 'Lerik'],
                        'ru' => ['name' => 'Лерик'],
                    ],
                    'map_location' => ['lat' => '38.7733', 'lng' => '48.4150'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Masallı'],
                        'en' => ['name' => 'Masalli'],
                        'ru' => ['name' => 'Масаллы'],
                    ],
                    'map_location' => ['lat' => '39.0333', 'lng' => '48.6667'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Naftalan'],
                        'en' => ['name' => 'Naftalan'],
                        'ru' => ['name' => 'Нафталан'],
                    ],
                    'map_location' => ['lat' => '40.5089', 'lng' => '46.8203'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Neftçala'],
                        'en' => ['name' => 'Neftchala'],
                        'ru' => ['name' => 'Нефтчала'],
                    ],
                    'map_location' => ['lat' => '39.3833', 'lng' => '49.2500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Oğuz'],
                        'en' => ['name' => 'Oghuz'],
                        'ru' => ['name' => 'Огуз'],
                    ],
                    'map_location' => ['lat' => '41.0667', 'lng' => '47.4667'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Qəbələ'],
                        'en' => ['name' => 'Gabala'],
                        'ru' => ['name' => 'Габала'],
                    ],
                    'map_location' => ['lat' => '40.9800', 'lng' => '47.8700'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Qax'],
                        'en' => ['name' => 'Gakh'],
                        'ru' => ['name' => 'Гах'],
                    ],
                    'map_location' => ['lat' => '41.4167', 'lng' => '46.9167'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Qazax'],
                        'en' => ['name' => 'Gazakh'],
                        'ru' => ['name' => 'Газах'],
                    ],
                    'map_location' => ['lat' => '41.0900', 'lng' => '45.3517'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Qobustan'],
                        'en' => ['name' => 'Gobustan'],
                        'ru' => ['name' => 'Гобустан'],
                    ],
                    'map_location' => ['lat' => '40.0833', 'lng' => '49.4167'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Quba'],
                        'en' => ['name' => 'Guba'],
                        'ru' => ['name' => 'Губа'],
                    ],
                    'map_location' => ['lat' => '41.3606', 'lng' => '48.5135'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Qubadlı'],
                        'en' => ['name' => 'Gubadli'],
                        'ru' => ['name' => 'Губадлы'],
                    ],
                    'map_location' => ['lat' => '39.3500', 'lng' => '46.6333'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Qusar'],
                        'en' => ['name' => 'Gusar'],
                        'ru' => ['name' => 'Гусар'],
                    ],
                    'map_location' => ['lat' => '41.4278', 'lng' => '48.4294'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Saatlı'],
                        'en' => ['name' => 'Saatli'],
                        'ru' => ['name' => 'Саатлы'],
                    ],
                    'map_location' => ['lat' => '39.9333', 'lng' => '48.3667'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Sabirabad'],
                        'en' => ['name' => 'Sabirabad'],
                        'ru' => ['name' => 'Сабирабад'],
                    ],
                    'map_location' => ['lat' => '40.0089', 'lng' => '48.4769'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şabran'],
                        'en' => ['name' => 'Shabran'],
                        'ru' => ['name' => 'Шабран'],
                    ],
                    'map_location' => ['lat' => '41.2167', 'lng' => '48.7000'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şamaxı'],
                        'en' => ['name' => 'Shamakhi'],
                        'ru' => ['name' => 'Шемаха'],
                    ],
                    'map_location' => ['lat' => '40.6317', 'lng' => '48.6350'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şəki'],
                        'en' => ['name' => 'Sheki'],
                        'ru' => ['name' => 'Шеки'],
                    ],
                    'map_location' => ['lat' => '41.1911', 'lng' => '47.1708'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şəmkir'],
                        'en' => ['name' => 'Shamkir'],
                        'ru' => ['name' => 'Шамкир'],
                    ],
                    'map_location' => ['lat' => '40.8307', 'lng' => '46.0170'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şuşa'],
                        'en' => ['name' => 'Shusha'],
                        'ru' => ['name' => 'Шуша'],
                    ],
                    'map_location' => ['lat' => '39.7600', 'lng' => '46.7500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Tərtər'],
                        'en' => ['name' => 'Tartar'],
                        'ru' => ['name' => 'Тертер'],
                    ],
                    'map_location' => ['lat' => '40.3333', 'lng' => '47.1000'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Tovuz'],
                        'en' => ['name' => 'Tovuz'],
                        'ru' => ['name' => 'Товуз'],
                    ],
                    'map_location' => ['lat' => '40.9922', 'lng' => '45.6294'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ucar'],
                        'en' => ['name' => 'Ujar'],
                        'ru' => ['name' => 'Уджары'],
                    ],
                    'map_location' => ['lat' => '40.5125', 'lng' => '47.6486'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Yardımlı'],
                        'en' => ['name' => 'Yardimli'],
                        'ru' => ['name' => 'Ярдымлы'],
                    ],
                    'map_location' => ['lat' => '38.9056', 'lng' => '48.2400'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Yevlax'],
                        'en' => ['name' => 'Yevlakh'],
                        'ru' => ['name' => 'Евлах'],
                    ],
                    'map_location' => ['lat' => '40.6167', 'lng' => '47.1500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Zaqatala'],
                        'en' => ['name' => 'Zagatala'],
                        'ru' => ['name' => 'Закатала'],
                    ],
                    'map_location' => ['lat' => '41.6474', 'lng' => '46.6430'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Zəngilan'],
                        'en' => ['name' => 'Zangilan'],
                        'ru' => ['name' => 'Зангелан'],
                    ],
                    'map_location' => ['lat' => '39.0833', 'lng' => '46.6500'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Zərdab'],
                        'en' => ['name' => 'Zardab'],
                        'ru' => ['name' => 'Зардоб'],
                    ],
                    'map_location' => ['lat' => '40.2167', 'lng' => '47.7167'],
                    'regions' => [],
                ],
            ],
        ];

        // Azərbaycanı yarat
        $cities = $azerbaijanData['cities'];
        unset($azerbaijanData['cities']);
        $country = Country::create($azerbaijanData);

        // Şəhərləri, rayonları və metroları əlavə et
        foreach ($cities as $cityData) {
            $regions = $cityData['regions'] ?? [];
            unset($cityData['regions']);
            $cityData['country_id'] = $country->id;
            $city = City::create($cityData);

            foreach ($regions as $regionData) {
                $subways = $regionData['subways'] ?? [];
                unset($regionData['subways']);
                $regionData['country_id'] = $country->id;
                $regionData['city_id'] = $city->id;
                $region = Region::create($regionData);

                foreach ($subways as $subwayData) {
                    $subwayData['country_id'] = $country->id;
                    $subwayData['city_id'] = $city->id;
                    $subwayData['region_id'] = $region->id;
                    Subway::create($subwayData);
                }
            }
        }
    }

    protected function createTurkey(): void
    {
        $turkeyData = [
            'translates' => [
                'az' => ['name' => 'Türkiyə'],
                'en' => ['name' => 'Turkey'],
                'ru' => ['name' => 'Турция'],
            ],
            'phone_code' => '90',
            'currency' => 'TRY',
            'map_location' => ['lat' => '38.9637', 'lng' => '35.2433'],
            'cities' => [
                [
                    'translates' => [
                        'az' => ['name' => 'İstanbul'],
                        'en' => ['name' => 'Istanbul'],
                        'ru' => ['name' => 'Стамбул'],
                    ],
                    'map_location' => ['lat' => '41.0082', 'lng' => '28.9784'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Beyoğlu'], 'en' => ['name' => 'Beyoglu'], 'ru' => ['name' => 'Бейоглу']], 'map_location' => ['lat' => '41.0370', 'lng' => '28.9770']],
                        ['translates' => ['az' => ['name' => 'Beşiktaş'], 'en' => ['name' => 'Besiktas'], 'ru' => ['name' => 'Бешикташ']], 'map_location' => ['lat' => '41.0430', 'lng' => '29.0070']],
                        ['translates' => ['az' => ['name' => 'Fatih'], 'en' => ['name' => 'Fatih'], 'ru' => ['name' => 'Фатих']], 'map_location' => ['lat' => '41.0080', 'lng' => '28.9500']],
                        ['translates' => ['az' => ['name' => 'Kadıköy'], 'en' => ['name' => 'Kadikoy'], 'ru' => ['name' => 'Кадыкёй']], 'map_location' => ['lat' => '40.9900', 'lng' => '29.0200']],
                        ['translates' => ['az' => ['name' => 'Üsküdar'], 'en' => ['name' => 'Uskudar'], 'ru' => ['name' => 'Юскюдар']], 'map_location' => ['lat' => '41.0250', 'lng' => '29.0130']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ankara'],
                        'en' => ['name' => 'Ankara'],
                        'ru' => ['name' => 'Анкара'],
                    ],
                    'map_location' => ['lat' => '39.9334', 'lng' => '32.8597'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Çankaya'], 'en' => ['name' => 'Cankaya'], 'ru' => ['name' => 'Чанкая']], 'map_location' => ['lat' => '39.9179', 'lng' => '32.8578']],
                        ['translates' => ['az' => ['name' => 'Keçiören'], 'en' => ['name' => 'Kecioren'], 'ru' => ['name' => 'Кечиорен']], 'map_location' => ['lat' => '39.9780', 'lng' => '32.8430']],
                        ['translates' => ['az' => ['name' => 'Yenimahalle'], 'en' => ['name' => 'Yenimahalle'], 'ru' => ['name' => 'Енимахалле']], 'map_location' => ['lat' => '39.9650', 'lng' => '32.8050']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'İzmir'],
                        'en' => ['name' => 'Izmir'],
                        'ru' => ['name' => 'Измир'],
                    ],
                    'map_location' => ['lat' => '38.4237', 'lng' => '27.1428'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Konak'], 'en' => ['name' => 'Konak'], 'ru' => ['name' => 'Конак']], 'map_location' => ['lat' => '38.4180', 'lng' => '27.1280']],
                        ['translates' => ['az' => ['name' => 'Karşıyaka'], 'en' => ['name' => 'Karsiyaka'], 'ru' => ['name' => 'Каршияка']], 'map_location' => ['lat' => '38.4570', 'lng' => '27.1150']],
                        ['translates' => ['az' => ['name' => 'Bornova'], 'en' => ['name' => 'Bornova'], 'ru' => ['name' => 'Борнова']], 'map_location' => ['lat' => '38.4660', 'lng' => '27.2200']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Adana'],
                        'en' => ['name' => 'Adana'],
                        'ru' => ['name' => 'Адана'],
                    ],
                    'map_location' => ['lat' => '36.9914', 'lng' => '35.3308'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Adıyaman'],
                        'en' => ['name' => 'Adiyaman'],
                        'ru' => ['name' => 'Адыяман'],
                    ],
                    'map_location' => ['lat' => '37.7636', 'lng' => '38.2773'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Afyonkarahisar'],
                        'en' => ['name' => 'Afyonkarahisar'],
                        'ru' => ['name' => 'Афьонкарахисар'],
                    ],
                    'map_location' => ['lat' => '38.7569', 'lng' => '30.5387'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ağrı'],
                        'en' => ['name' => 'Agri'],
                        'ru' => ['name' => 'Агры'],
                    ],
                    'map_location' => ['lat' => '39.7191', 'lng' => '43.0514'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Aksaray'],
                        'en' => ['name' => 'Aksaray'],
                        'ru' => ['name' => 'Аксарай'],
                    ],
                    'map_location' => ['lat' => '38.3687', 'lng' => '34.0297'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Amasya'],
                        'en' => ['name' => 'Amasya'],
                        'ru' => ['name' => 'Амасья'],
                    ],
                    'map_location' => ['lat' => '40.6500', 'lng' => '35.8333'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Antalya'],
                        'en' => ['name' => 'Antalya'],
                        'ru' => ['name' => 'Анталья'],
                    ],
                    'map_location' => ['lat' => '36.8969', 'lng' => '30.7133'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ardahan'],
                        'en' => ['name' => 'Ardahan'],
                        'ru' => ['name' => 'Ардахан'],
                    ],
                    'map_location' => ['lat' => '41.1087', 'lng' => '42.7022'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Artvin'],
                        'en' => ['name' => 'Artvin'],
                        'ru' => ['name' => 'Артвин'],
                    ],
                    'map_location' => ['lat' => '41.1816', 'lng' => '41.8208'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Aydın'],
                        'en' => ['name' => 'Aydin'],
                        'ru' => ['name' => 'Айдын'],
                    ],
                    'map_location' => ['lat' => '37.8480', 'lng' => '27.8453'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Balıkesir'],
                        'en' => ['name' => 'Balikesir'],
                        'ru' => ['name' => 'Балыкесир'],
                    ],
                    'map_location' => ['lat' => '39.6484', 'lng' => '27.8826'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Bartın'],
                        'en' => ['name' => 'Bartin'],
                        'ru' => ['name' => 'Бартын'],
                    ],
                    'map_location' => ['lat' => '41.6345', 'lng' => '32.3378'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Batman'],
                        'en' => ['name' => 'Batman'],
                        'ru' => ['name' => 'Батман'],
                    ],
                    'map_location' => ['lat' => '37.8833', 'lng' => '41.1351'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Bayburt'],
                        'en' => ['name' => 'Bayburt'],
                        'ru' => ['name' => 'Байбурт'],
                    ],
                    'map_location' => ['lat' => '40.2552', 'lng' => '40.2249'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Bilecik'],
                        'en' => ['name' => 'Bilecik'],
                        'ru' => ['name' => 'Билечик'],
                    ],
                    'map_location' => ['lat' => '40.1419', 'lng' => '29.9793'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Bingöl'],
                        'en' => ['name' => 'Bingol'],
                        'ru' => ['name' => 'Бингёль'],
                    ],
                    'map_location' => ['lat' => '38.8853', 'lng' => '40.4983'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Bitlis'],
                        'en' => ['name' => 'Bitlis'],
                        'ru' => ['name' => 'Битлис'],
                    ],
                    'map_location' => ['lat' => '38.3938', 'lng' => '42.1232'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Bolu'],
                        'en' => ['name' => 'Bolu'],
                        'ru' => ['name' => 'Болу'],
                    ],
                    'map_location' => ['lat' => '40.7358', 'lng' => '31.6062'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Burdur'],
                        'en' => ['name' => 'Burdur'],
                        'ru' => ['name' => 'Бурдур'],
                    ],
                    'map_location' => ['lat' => '37.7203', 'lng' => '30.2908'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Bursa'],
                        'en' => ['name' => 'Bursa'],
                        'ru' => ['name' => 'Бурса'],
                    ],
                    'map_location' => ['lat' => '40.1820', 'lng' => '29.0670'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Çanakkale'],
                        'en' => ['name' => 'Canakkale'],
                        'ru' => ['name' => 'Чанаккале'],
                    ],
                    'map_location' => ['lat' => '40.1467', 'lng' => '26.4086'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Çankırı'],
                        'en' => ['name' => 'Cankiri'],
                        'ru' => ['name' => 'Чанкыры'],
                    ],
                    'map_location' => ['lat' => '40.6000', 'lng' => '33.6167'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Çorum'],
                        'en' => ['name' => 'Corum'],
                        'ru' => ['name' => 'Чорум'],
                    ],
                    'map_location' => ['lat' => '40.5489', 'lng' => '34.9537'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Denizli'],
                        'en' => ['name' => 'Denizli'],
                        'ru' => ['name' => 'Денизли'],
                    ],
                    'map_location' => ['lat' => '37.7833', 'lng' => '29.0963'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Diyarbakır'],
                        'en' => ['name' => 'Diyarbakir'],
                        'ru' => ['name' => 'Диярбакыр'],
                    ],
                    'map_location' => ['lat' => '37.9250', 'lng' => '40.2100'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Düzce'],
                        'en' => ['name' => 'Duzce'],
                        'ru' => ['name' => 'Дюздже'],
                    ],
                    'map_location' => ['lat' => '40.8389', 'lng' => '31.1640'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Edirne'],
                        'en' => ['name' => 'Edirne'],
                        'ru' => ['name' => 'Эдирне'],
                    ],
                    'map_location' => ['lat' => '41.6771', 'lng' => '26.5557'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Elazığ'],
                        'en' => ['name' => 'Elazig'],
                        'ru' => ['name' => 'Элязыг'],
                    ],
                    'map_location' => ['lat' => '38.6743', 'lng' => '39.2232'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Erzincan'],
                        'en' => ['name' => 'Erzincan'],
                        'ru' => ['name' => 'Эрзинджан'],
                    ],
                    'map_location' => ['lat' => '39.7462', 'lng' => '39.4914'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Erzurum'],
                        'en' => ['name' => 'Erzurum'],
                        'ru' => ['name' => 'Эрзурум'],
                    ],
                    'map_location' => ['lat' => '39.9055', 'lng' => '41.2769'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Eskişehir'],
                        'en' => ['name' => 'Eskisehir'],
                        'ru' => ['name' => 'Эскишехир'],
                    ],
                    'map_location' => ['lat' => '39.7767', 'lng' => '30.5206'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Gaziantep'],
                        'en' => ['name' => 'Gaziantep'],
                        'ru' => ['name' => 'Газиантеп'],
                    ],
                    'map_location' => ['lat' => '37.0662', 'lng' => '37.3781'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Giresun'],
                        'en' => ['name' => 'Giresun'],
                        'ru' => ['name' => 'Гиресун'],
                    ],
                    'map_location' => ['lat' => '40.9128', 'lng' => '38.3895'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Gümüşhane'],
                        'en' => ['name' => 'Gumushane'],
                        'ru' => ['name' => 'Гюмюшхане'],
                    ],
                    'map_location' => ['lat' => '40.4386', 'lng' => '39.4718'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Hakkari'],
                        'en' => ['name' => 'Hakkari'],
                        'ru' => ['name' => 'Хаккари'],
                    ],
                    'map_location' => ['lat' => '37.5833', 'lng' => '43.7333'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Hatay'],
                        'en' => ['name' => 'Hatay'],
                        'ru' => ['name' => 'Хатай'],
                    ],
                    'map_location' => ['lat' => '36.2000', 'lng' => '36.1667'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Iğdır'],
                        'en' => ['name' => 'Igdir'],
                        'ru' => ['name' => 'Ыгдыр'],
                    ],
                    'map_location' => ['lat' => '39.9167', 'lng' => '44.0333'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Isparta'],
                        'en' => ['name' => 'Isparta'],
                        'ru' => ['name' => 'Ыспарта'],
                    ],
                    'map_location' => ['lat' => '37.7648', 'lng' => '30.5566'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kahramanmaraş'],
                        'en' => ['name' => 'Kahramanmaras'],
                        'ru' => ['name' => 'Кахраманмараш'],
                    ],
                    'map_location' => ['lat' => '37.5753', 'lng' => '36.9228'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Karabük'],
                        'en' => ['name' => 'Karabuk'],
                        'ru' => ['name' => 'Карабюк'],
                    ],
                    'map_location' => ['lat' => '41.1980', 'lng' => '32.6267'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Karamann'],
                        'en' => ['name' => 'Karaman'],
                        'ru' => ['name' => 'Караман'],
                    ],
                    'map_location' => ['lat' => '37.1811', 'lng' => '33.2150'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kars'],
                        'en' => ['name' => 'Kars'],
                        'ru' => ['name' => 'Карс'],
                    ],
                    'map_location' => ['lat' => '40.5983', 'lng' => '43.0856'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kastamonu'],
                        'en' => ['name' => 'Kastamonu'],
                        'ru' => ['name' => 'Кастамону'],
                    ],
                    'map_location' => ['lat' => '41.3766', 'lng' => '33.7753'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kayseri'],
                        'en' => ['name' => 'Kayseri'],
                        'ru' => ['name' => 'Кайсери'],
                    ],
                    'map_location' => ['lat' => '38.7312', 'lng' => '35.4787'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kırıkkale'],
                        'en' => ['name' => 'Kirikkale'],
                        'ru' => ['name' => 'Кырыккале'],
                    ],
                    'map_location' => ['lat' => '39.8468', 'lng' => '33.5153'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kırklareli'],
                        'en' => ['name' => 'Kirklareli'],
                        'ru' => ['name' => 'Кыркларели'],
                    ],
                    'map_location' => ['lat' => '41.7351', 'lng' => '27.2252'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kırşehir'],
                        'en' => ['name' => 'Kirsehir'],
                        'ru' => ['name' => 'Кыршехир'],
                    ],
                    'map_location' => ['lat' => '39.1458', 'lng' => '34.1639'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kilis'],
                        'en' => ['name' => 'Kilis'],
                        'ru' => ['name' => 'Килис'],
                    ],
                    'map_location' => ['lat' => '36.7161', 'lng' => '37.1150'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kocaeli'],
                        'en' => ['name' => 'Kocaeli'],
                        'ru' => ['name' => 'Коджаэли'],
                    ],
                    'map_location' => ['lat' => '40.8533', 'lng' => '29.8815'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Konya'],
                        'en' => ['name' => 'Konya'],
                        'ru' => ['name' => 'Конья'],
                    ],
                    'map_location' => ['lat' => '37.8746', 'lng' => '32.4932'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kütahya'],
                        'en' => ['name' => 'Kutahya'],
                        'ru' => ['name' => 'Кютахья'],
                    ],
                    'map_location' => ['lat' => '39.4167', 'lng' => '29.9833'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Malatya'],
                        'en' => ['name' => 'Malatya'],
                        'ru' => ['name' => 'Малатья'],
                    ],
                    'map_location' => ['lat' => '38.3552', 'lng' => '38.3095'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Manisa'],
                        'en' => ['name' => 'Manisa'],
                        'ru' => ['name' => 'Маниса'],
                    ],
                    'map_location' => ['lat' => '38.6191', 'lng' => '27.4289'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Mardin'],
                        'en' => ['name' => 'Mardin'],
                        'ru' => ['name' => 'Мардин'],
                    ],
                    'map_location' => ['lat' => '37.3123', 'lng' => '40.7351'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Mersin'],
                        'en' => ['name' => 'Mersin'],
                        'ru' => ['name' => 'Мерсин'],
                    ],
                    'map_location' => ['lat' => '36.8121', 'lng' => '34.6415'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Muğla'],
                        'en' => ['name' => 'Mugla'],
                        'ru' => ['name' => 'Мугла'],
                    ],
                    'map_location' => ['lat' => '37.2153', 'lng' => '28.3637'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Muş'],
                        'en' => ['name' => 'Mus'],
                        'ru' => ['name' => 'Муш'],
                    ],
                    'map_location' => ['lat' => '38.7351', 'lng' => '41.4910'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Nevşehir'],
                        'en' => ['name' => 'Nevsehir'],
                        'ru' => ['name' => 'Невшехир'],
                    ],
                    'map_location' => ['lat' => '38.6247', 'lng' => '34.7236'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Niğde'],
                        'en' => ['name' => 'Nigde'],
                        'ru' => ['name' => 'Нигде'],
                    ],
                    'map_location' => ['lat' => '37.9667', 'lng' => '34.6833'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ordu'],
                        'en' => ['name' => 'Ordu'],
                        'ru' => ['name' => 'Орду'],
                    ],
                    'map_location' => ['lat' => '40.9862', 'lng' => '37.8797'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Osmaniye'],
                        'en' => ['name' => 'Osmaniye'],
                        'ru' => ['name' => 'Османие'],
                    ],
                    'map_location' => ['lat' => '37.0746', 'lng' => '36.2479'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Rize'],
                        'en' => ['name' => 'Rize'],
                        'ru' => ['name' => 'Ризе'],
                    ],
                    'map_location' => ['lat' => '41.0208', 'lng' => '40.5219'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Sakarya'],
                        'en' => ['name' => 'Sakarya'],
                        'ru' => ['name' => 'Сакарья'],
                    ],
                    'map_location' => ['lat' => '40.7806', 'lng' => '30.4033'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Samsun'],
                        'en' => ['name' => 'Samsun'],
                        'ru' => ['name' => 'Самсун'],
                    ],
                    'map_location' => ['lat' => '41.2867', 'lng' => '36.3300'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Siirt'],
                        'en' => ['name' => 'Siirt'],
                        'ru' => ['name' => 'Сиирт'],
                    ],
                    'map_location' => ['lat' => '37.9274', 'lng' => '41.9410'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Sinop'],
                        'en' => ['name' => 'Sinop'],
                        'ru' => ['name' => 'Синоп'],
                    ],
                    'map_location' => ['lat' => '42.0267', 'lng' => '35.1511'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Sivas'],
                        'en' => ['name' => 'Sivas'],
                        'ru' => ['name' => 'Сивас'],
                    ],
                    'map_location' => ['lat' => '39.7477', 'lng' => '37.0179'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şanlıurfa'],
                        'en' => ['name' => 'Sanliurfa'],
                        'ru' => ['name' => 'Шанлыурфа'],
                    ],
                    'map_location' => ['lat' => '37.1671', 'lng' => '38.7939'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Şırnak'],
                        'en' => ['name' => 'Sirnak'],
                        'ru' => ['name' => 'Шырнак'],
                    ],
                    'map_location' => ['lat' => '37.4187', 'lng' => '42.4918'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Tekirdağ'],
                        'en' => ['name' => 'Tekirdag'],
                        'ru' => ['name' => 'Текирдаг'],
                    ],
                    'map_location' => ['lat' => '40.9781', 'lng' => '27.5117'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Tokat'],
                        'en' => ['name' => 'Tokat'],
                        'ru' => ['name' => 'Токат'],
                    ],
                    'map_location' => ['lat' => '40.3139', 'lng' => '36.5544'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Trabzon'],
                        'en' => ['name' => 'Trabzon'],
                        'ru' => ['name' => 'Трабзон'],
                    ],
                    'map_location' => ['lat' => '41.0027', 'lng' => '39.7168'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Tunceli'],
                        'en' => ['name' => 'Tunceli'],
                        'ru' => ['name' => 'Тунджели'],
                    ],
                    'map_location' => ['lat' => '39.1083', 'lng' => '39.5471'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Uşak'],
                        'en' => ['name' => 'Usak'],
                        'ru' => ['name' => 'Ушак'],
                    ],
                    'map_location' => ['lat' => '38.6745', 'lng' => '29.4059'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Van'],
                        'en' => ['name' => 'Van'],
                        'ru' => ['name' => 'Ван'],
                    ],
                    'map_location' => ['lat' => '38.5012', 'lng' => '43.3730'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Yalova'],
                        'en' => ['name' => 'Yalova'],
                        'ru' => ['name' => 'Ялова'],
                    ],
                    'map_location' => ['lat' => '40.6549', 'lng' => '29.2842'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Yozgat'],
                        'en' => ['name' => 'Yozgat'],
                        'ru' => ['name' => 'Йозгат'],
                    ],
                    'map_location' => ['lat' => '39.8200', 'lng' => '34.8044'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Zonguldak'],
                        'en' => ['name' => 'Zonguldak'],
                        'ru' => ['name' => 'Зонгулдак'],
                    ],
                    'map_location' => ['lat' => '41.4564', 'lng' => '31.7987'],
                    'regions' => [],
                ],
            ],
        ];

        // Türkiyəni yarat
        $cities = $turkeyData['cities'];
        unset($turkeyData['cities']);
        $country = Country::create($turkeyData);

        // Şəhərləri və rayonları əlavə et
        foreach ($cities as $cityData) {
            $regions = $cityData['regions'] ?? [];
            unset($cityData['regions']);
            $cityData['country_id'] = $country->id;
            $city = City::create($cityData);

            foreach ($regions as $regionData) {
                $regionData['country_id'] = $country->id;
                $regionData['city_id'] = $city->id;
                Region::create($regionData);
            }
        }
    }

    protected function createGeorgia(): void
    {
        $georgiaData = [
            'translates' => [
                'az' => ['name' => 'Gürcüstan'],
                'en' => ['name' => 'Georgia'],
                'ru' => ['name' => 'Грузия'],
            ],
            'phone_code' => '995',
            'currency' => 'GEL',
            'map_location' => ['lat' => '42.3154', 'lng' => '43.3569'],
            'cities' => [
                [
                    'translates' => [
                        'az' => ['name' => 'Tbilisi'],
                        'en' => ['name' => 'Tbilisi'],
                        'ru' => ['name' => 'Тбилиси'],
                    ],
                    'map_location' => ['lat' => '41.7151', 'lng' => '44.8271'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Vake'], 'en' => ['name' => 'Vake'], 'ru' => ['name' => 'Ваке']], 'map_location' => ['lat' => '41.7090', 'lng' => '44.7629']],
                        ['translates' => ['az' => ['name' => 'Saburtalo'], 'en' => ['name' => 'Saburtalo'], 'ru' => ['name' => 'Сабуртало']], 'map_location' => ['lat' => '41.7350', 'lng' => '44.7460']],
                        ['translates' => ['az' => ['name' => 'Mtatsminda'], 'en' => ['name' => 'Mtatsminda'], 'ru' => ['name' => 'Мтацминда']], 'map_location' => ['lat' => '41.6940', 'lng' => '44.7990']],
                        ['translates' => ['az' => ['name' => 'Gldani'], 'en' => ['name' => 'Gldani'], 'ru' => ['name' => 'Глдани']], 'map_location' => ['lat' => '41.7790', 'lng' => '44.8150']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Batumi'],
                        'en' => ['name' => 'Batumi'],
                        'ru' => ['name' => 'Батуми'],
                    ],
                    'map_location' => ['lat' => '41.6168', 'lng' => '41.6339'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Köhnə Batumi'], 'en' => ['name' => 'Old Batumi'], 'ru' => ['name' => 'Старый Батуми']], 'map_location' => ['lat' => '41.6500', 'lng' => '41.6390']],
                        ['translates' => ['az' => ['name' => 'Yeni Bulvar'], 'en' => ['name' => 'New Boulevard'], 'ru' => ['name' => 'Новый Бульвар']], 'map_location' => ['lat' => '41.6330', 'lng' => '41.6160']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Kutaisi'],
                        'en' => ['name' => 'Kutaisi'],
                        'ru' => ['name' => 'Кутаиси'],
                    ],
                    'map_location' => ['lat' => '42.2660', 'lng' => '42.7180'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Rustavi'],
                        'en' => ['name' => 'Rustavi'],
                        'ru' => ['name' => 'Рустави'],
                    ],
                    'map_location' => ['lat' => '41.5411', 'lng' => '45.0277'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Gori'],
                        'en' => ['name' => 'Gori'],
                        'ru' => ['name' => 'Гори'],
                    ],
                    'map_location' => ['lat' => '41.9854', 'lng' => '44.1084'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Zugdidi'],
                        'en' => ['name' => 'Zugdidi'],
                        'ru' => ['name' => 'Зугдиди'],
                    ],
                    'map_location' => ['lat' => '42.5088', 'lng' => '41.8669'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Poti'],
                        'en' => ['name' => 'Poti'],
                        'ru' => ['name' => 'Поти'],
                    ],
                    'map_location' => ['lat' => '42.1583', 'lng' => '41.6716'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Telavi'],
                        'en' => ['name' => 'Telavi'],
                        'ru' => ['name' => 'Телави'],
                    ],
                    'map_location' => ['lat' => '41.9198', 'lng' => '45.4731'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Akhaltsikhe'],
                        'en' => ['name' => 'Akhaltsikhe'],
                        'ru' => ['name' => 'Ахалцихе'],
                    ],
                    'map_location' => ['lat' => '41.6390', 'lng' => '42.9860'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Ozurgeti'],
                        'en' => ['name' => 'Ozurgeti'],
                        'ru' => ['name' => 'Озургети'],
                    ],
                    'map_location' => ['lat' => '41.9205', 'lng' => '42.0033'],
                    'regions' => [],
                ],
            ],
        ];

        // Gürcüstanı yarat
        $cities = $georgiaData['cities'];
        unset($georgiaData['cities']);
        $country = Country::create($georgiaData);

        // Şəhərləri və rayonları əlavə et
        foreach ($cities as $cityData) {
            $regions = $cityData['regions'] ?? [];
            unset($cityData['regions']);
            $cityData['country_id'] = $country->id;
            $city = City::create($cityData);

            foreach ($regions as $regionData) {
                $regionData['country_id'] = $country->id;
                $regionData['city_id'] = $city->id;
                Region::create($regionData);
            }
        }
    }

    protected function createRussia(): void
    {
        $russiaData = [
            'translates' => [
                'az' => ['name' => 'Rusiya'],
                'en' => ['name' => 'Russia'],
                'ru' => ['name' => 'Россия'],
            ],
            'phone_code' => '7',
            'currency' => 'RUB',
            'map_location' => ['lat' => '61.5240', 'lng' => '105.3188'],
            'cities' => [
                [
                    'translates' => [
                        'az' => ['name' => 'Moskva'],
                        'en' => ['name' => 'Moscow'],
                        'ru' => ['name' => 'Москва'],
                    ],
                    'map_location' => ['lat' => '55.7558', 'lng' => '37.6173'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Tverskoy'], 'en' => ['name' => 'Tverskoy'], 'ru' => ['name' => 'Тверской']], 'map_location' => ['lat' => '55.7600', 'lng' => '37.6090']],
                        ['translates' => ['az' => ['name' => 'Arbat'], 'en' => ['name' => 'Arbat'], 'ru' => ['name' => 'Арбат']], 'map_location' => ['lat' => '55.7500', 'lng' => '37.5900']],
                        ['translates' => ['az' => ['name' => 'Presnenskiy'], 'en' => ['name' => 'Presnenskiy'], 'ru' => ['name' => 'Пресненский']], 'map_location' => ['lat' => '55.7600', 'lng' => '37.5700']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Sankt-Peterburq'],
                        'en' => ['name' => 'Saint Petersburg'],
                        'ru' => ['name' => 'Санкт-Петербург'],
                    ],
                    'map_location' => ['lat' => '59.9343', 'lng' => '30.3351'],
                    'regions' => [
                        ['translates' => ['az' => ['name' => 'Admiralteyskiy'], 'en' => ['name' => 'Admiralteyskiy'], 'ru' => ['name' => 'Адмиралтейский']], 'map_location' => ['lat' => '59.9300', 'lng' => '30.3100']],
                        ['translates' => ['az' => ['name' => 'Vasileostrovskiy'], 'en' => ['name' => 'Vasileostrovskiy'], 'ru' => ['name' => 'Василеостровский']], 'map_location' => ['lat' => '59.9400', 'lng' => '30.2600']],
                    ],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Mahaçqala'],
                        'en' => ['name' => 'Makhachkala'],
                        'ru' => ['name' => 'Махачкала'],
                    ],
                    'map_location' => ['lat' => '42.9830', 'lng' => '47.5047'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Vladikavkaz'],
                        'en' => ['name' => 'Vladikavkaz'],
                        'ru' => ['name' => 'Владикавказ'],
                    ],
                    'map_location' => ['lat' => '43.0252', 'lng' => '44.6818'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Grozni'],
                        'en' => ['name' => 'Grozny'],
                        'ru' => ['name' => 'Грозный'],
                    ],
                    'map_location' => ['lat' => '43.3170', 'lng' => '45.6987'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Derbent'],
                        'en' => ['name' => 'Derbent'],
                        'ru' => ['name' => 'Дербент'],
                    ],
                    'map_location' => ['lat' => '42.0578', 'lng' => '48.2899'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Nalçik'],
                        'en' => ['name' => 'Nalchik'],
                        'ru' => ['name' => 'Нальчик'],
                    ],
                    'map_location' => ['lat' => '43.4833', 'lng' => '43.6167'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Rostov-na-Donu'],
                        'en' => ['name' => 'Rostov-on-Don'],
                        'ru' => ['name' => 'Ростов-на-Дону'],
                    ],
                    'map_location' => ['lat' => '47.2357', 'lng' => '39.7015'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Novosibirsk'],
                        'en' => ['name' => 'Novosibirsk'],
                        'ru' => ['name' => 'Новосибирск'],
                    ],
                    'map_location' => ['lat' => '55.0084', 'lng' => '82.9357'],
                    'regions' => [],
                ],
                [
                    'translates' => [
                        'az' => ['name' => 'Yekaterinburq'],
                        'en' => ['name' => 'Yekaterinburg'],
                        'ru' => ['name' => 'Екатеринбург'],
                    ],
                    'map_location' => ['lat' => '56.8389', 'lng' => '60.6057'],
                    'regions' => [],
                ],
            ],
        ];

        // Rusiyanı yarat
        $cities = $russiaData['cities'];
        unset($russiaData['cities']);
        $country = Country::create($russiaData);

        // Şəhərləri və rayonları əlavə et
        foreach ($cities as $cityData) {
            $regions = $cityData['regions'] ?? [];
            unset($cityData['regions']);
            $cityData['country_id'] = $country->id;
            $city = City::create($cityData);

            foreach ($regions as $regionData) {
                $regionData['country_id'] = $country->id;
                $regionData['city_id'] = $city->id;
                Region::create($regionData);
            }
        }
    }
}
