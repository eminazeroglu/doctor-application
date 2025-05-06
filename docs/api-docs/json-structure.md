# API Documentation JSON Structure Documentation

## Ümumi Məlumat

Bu sənəd API dokumentasiyası üçün istifadə edilən JSON strukturunu detallı şəkildə izah edir. Bu struktur, API endpoint-lərinin düzxətli siyahı formatında təsvir edilməsini təmin edir və daha asan proqram emalına imkan verir.

## 📁 Qovluq Strukturu

```
swagger/
└── posts.json
```

## 🔧 Əsas Struktur

Hər bir modul üçün JSON faylı:

```json
{
    "tag": "Module Name",
    "description": "Module Description",
    "endpoints": [
        {
            "path": "/route-path",
            "method": "get",
            ...
        }
    ]
}
```

## 🛠️ Endpoint Konfiqurasiyası

### Tam Endpoint Strukturu

```json
{
    "path": "/route-path",
    "method": "get",
    "summary": "Qısa başlıq",
    "description": "Ətraflı açıqlama",
    "authorization": true,
    "query": {},
    "formData": false,
    "formBody": {},
    "responses": {}
}
```

### Query Parameters

Query parametrləri GET sorğuları üçün istifadə olunur:

```json
"query": {
    "search": {
        "type": "string",
        "description": "Axtarış sözü",
        "required": false
    },
    "status": {
        "type": "string",
        "description": "Status filtri",
        "default": "active"
    },
    "categoryId": {
        "type": "number",
        "description": "Kateqoriya ID",
        "required": true,
        "default": 1
    },
    "page": {
        "type": "number",
        "description": "Səhifə nömrəsi",
        "default": 1
    },
    "limit": {
        "type": "number",
        "description": "Səhifə limiti",
        "default": 20
    }
}
```

### Form Body və FormData

API-yə məlumat göndərmək üçün iki üsul var: standart JSON formatında və FormData formatında. FormData formatını istifadə etmək üçün `formData: true` parametri təyin olunmalıdır. Bu, xüsusilə fayl yükləmə əməliyyatları üçün vacibdir.

```json
"formData": true,
"formBody": {
    "title": {
        "type": "string",
        "required": true,
        "description": "Başlıq",
        "default": "Default Title"
    },
    "description": {
        "type": "textarea",
        "description": "Açıqlama",
        "rows": 4
    },
    "category_id": {
        "type": "select",
        "required": true,
        "description": "Kateqoriya",
        "options": [
            {"value": "1", "label": "Texnologiya"},
            {"value": "2", "label": "Səhiyyə"},
            {"value": "3", "label": "Təhsil"}
        ],
        "default": "1"
    },
    "is_featured": {
        "type": "boolean",
        "description": "Seçilmiş elan",
        "default": "0"
    },
    "price": {
        "type": "number",
        "required": true,
        "description": "Qiymət",
        "default": 0
    },
    "photo": {
        "type": "file",
        "description": "Əsas şəkil",
        "accept": "image/*"
    },
    "gallery[]": {
        "type": "file",
        "description": "Qalereya şəkilləri",
        "multiple": true,
        "isArray": true,
        "accept": "image/*"
    },
    "contact[name]": {
        "type": "string",
        "description": "Əlaqə şəxsinin adı"
    },
    "contact[phones][]": {
        "type": "string",
        "description": "Əlaqə telefonu",
        "isArray": true
    },
    "location[coordinates][latitude]": {
        "type": "string",
        "description": "Enlik dərəcəsi"
    },
    "location[coordinates][longitude]": {
        "type": "string",
        "description": "Uzunluq dərəcəsi"
    }
}
```

#### Input növləri

FormData ilə istifadə edilə bilən input növləri:

- `type: "string"` - Standart mətn sahəsi
- `type: "number"` - Ədədi dəyər sahəsi
- `type: "textarea"` - Çoxsətirli mətn sahəsi
- `type: "select"` - Seçim siyahısı (dropdown)
- `type: "boolean"` - Məntiqi dəyər (0/1)
- `type: "file"` - Fayl yükləmə sahəsi
- `type: "array"` - Massiv dəyərlər
- `type: "object"` - JSON obyekt dəyərləri

#### Nested struktur və massivlər

FormData ilə nested struktur və massivlər yaratmaq mümkündür:

1. Nested obyektlər: `"contact[name]"`, `"location[coordinates][latitude]"`
2. Massivlər: `"gallery[]"`, `"contact[phones][]"`

### Response Formatları

#### List Response (Siyahı)

```json
"responses": {
    "data": [
        {
            "id": 1,
            "title": "Example"
        }
    ],
    "total": 50
}
```

#### Single Response (Tək element)

```json
"responses": {
    "200": {
        "id": 1,
        "title": "Example"
    }
}
```

#### Error Response (Xəta)

```json
"responses": {
    "422": {
        "title": "Başlıq mütləqdir"
    },
    "404": {
        "message": "Məlumat tapılmadı"
    }
}
```

## 📝 FormData ilə Tam CRUD Nümunəsi (listings.json)

```json
{
    "tag": "Listings",
    "description": "Elanlar ilə əlaqəli API endpoint-ləri",
    "endpoints": [
        {
            "path": "/listings",
            "method": "get",
            "summary": "Elanlar siyahısı",
            "description": "Bütün elanların siyahısını qaytarır",
            "query": {
                "search": {
                    "type": "string",
                    "description": "Başlığa görə axtarış"
                },
                "category_id": {
                    "type": "number",
                    "description": "Kateqoriya filtri",
                    "default": 1
                },
                "status": {
                    "type": "string",
                    "description": "Status filtri (active/passive)",
                    "default": "active"
                },
                "page": {
                    "type": "number",
                    "description": "Səhifə nömrəsi",
                    "default": 1
                },
                "limit": {
                    "type": "number",
                    "description": "Səhifə limiti",
                    "default": 20
                }
            },
            "responses": {
                "data": [
                    {
                        "id": 1,
                        "title": "Satılır 3 otaqlı mənzil",
                        "slug": "satilir-3-otaqli-menzil",
                        "description": "Təmirli, işıqlı mənzil",
                        "price": 150000,
                        "category_id": 1,
                        "status": "active",
                        "created_at": "2024-01-01 00:00:00"
                    }
                ],
                "total": 50
            }
        },
        {
            "path": "/listings",
            "method": "post",
            "summary": "Yeni elan",
            "description": "Yeni elan əlavə edir",
            "authorization": true,
            "formData": true,
            "formBody": {
                "title": {
                    "type": "string",
                    "required": true,
                    "description": "Elan başlığı"
                },
                "description": {
                    "type": "textarea",
                    "required": true,
                    "description": "Elanın təsviri",
                    "rows": 4
                },
                "price": {
                    "type": "number",
                    "required": true,
                    "description": "Qiymət",
                    "default": 0
                },
                "category_id": {
                    "type": "select",
                    "required": true,
                    "description": "Kateqoriya",
                    "options": [
                        {"value": "1", "label": "Mənzil"},
                        {"value": "2", "label": "Həyət evi"},
                        {"value": "3", "label": "Torpaq"}
                    ],
                    "default": "1"
                },
                "country_id": {
                    "type": "string",
                    "required": true,
                    "description": "Ölkə"
                },
                "city_id": {
                    "type": "string",
                    "required": true,
                    "description": "Şəhər"
                },
                "contact[name]": {
                    "type": "string",
                    "required": true,
                    "description": "Əlaqə saxlanılacaq şəxsin adı"
                },
                "contact[phones][]": {
                    "type": "string",
                    "required": true,
                    "description": "Əlaqə telefonu",
                    "isArray": true
                },
                "location[address]": {
                    "type": "string",
                    "required": true,
                    "description": "Ünvan"
                },
                "location[coordinates][latitude]": {
                    "type": "string",
                    "required": true,
                    "description": "Enlik dərəcəsi"
                },
                "location[coordinates][longitude]": {
                    "type": "string",
                    "required": true,
                    "description": "Uzunluq dərəcəsi"
                },
                "images[]": {
                    "type": "file",
                    "description": "Şəkillər",
                    "multiple": true,
                    "isArray": true,
                    "accept": "image/*"
                },
                "is_negotiable": {
                    "type": "boolean",
                    "description": "Qiymət danışıq yolu ilə",
                    "default": "0"
                },
                "is_new": {
                    "type": "boolean",
                    "description": "Yeni",
                    "default": "0"
                },
                "is_exchange": {
                    "type": "boolean",
                    "description": "Mübadilə mümkündür",
                    "default": "0"
                },
                "is_credit": {
                    "type": "boolean",
                    "description": "Kreditdədir",
                    "default": "0"
                }
            },
            "responses": {
                "200": {
                    "id": 1,
                    "title": "Satılır 3 otaqlı mənzil",
                    "slug": "satilir-3-otaqli-menzil",
                    "description": "Təmirli, işıqlı mənzil",
                    "price": 150000,
                    "category_id": 1,
                    "status": "active",
                    "created_at": "2024-01-01 00:00:00"
                },
                "422": {
                    "title": "Başlıq mütləqdir",
                    "description": "Təsvir mütləqdir",
                    "price": "Qiymət mütləqdir",
                    "category_id": "Kateqoriya mütləqdir"
                }
            }
        },
        {
            "path": "/listings/{id}",
            "method": "get",
            "summary": "Elan detalları",
            "description": "Elanın detallarını qaytarır",
            "authorization": true,
            "responses": {
                "200": {
                    "id": 1,
                    "title": "Satılır 3 otaqlı mənzil",
                    "slug": "satilir-3-otaqli-menzil",
                    "description": "Təmirli, işıqlı mənzil",
                    "price": 150000,
                    "category_id": 1,
                    "status": "active",
                    "created_at": "2024-01-01 00:00:00"
                },
                "404": {
                    "message": "Elan tapılmadı"
                }
            }
        },
        {
            "path": "/listings/{id}",
            "method": "put",
            "summary": "Elan yeniləmə",
            "description": "Elan məlumatlarını yeniləyir",
            "authorization": true,
            "formData": true,
            "formBody": {
                "title": {
                    "type": "string",
                    "required": true,
                    "description": "Elan başlığı"
                },
                "description": {
                    "type": "textarea",
                    "required": true,
                    "description": "Elanın təsviri",
                    "rows": 4
                },
                "price": {
                    "type": "number",
                    "required": true,
                    "description": "Qiymət"
                },
                "category_id": {
                    "type": "select",
                    "required": true,
                    "description": "Kateqoriya",
                    "options": [
                        {"value": "1", "label": "Mənzil"},
                        {"value": "2", "label": "Həyət evi"},
                        {"value": "3", "label": "Torpaq"}
                    ]
                },
                "country_id": {
                    "type": "string",
                    "required": true,
                    "description": "Ölkə"
                },
                "city_id": {
                    "type": "string",
                    "required": true,
                    "description": "Şəhər"
                },
                "contact[name]": {
                    "type": "string",
                    "required": true,
                    "description": "Əlaqə saxlanılacaq şəxsin adı"
                },
                "contact[phones][]": {
                    "type": "string",
                    "required": true,
                    "description": "Əlaqə telefonu",
                    "isArray": true
                },
                "location[address]": {
                    "type": "string",
                    "required": true,
                    "description": "Ünvan"
                },
                "location[coordinates][latitude]": {
                    "type": "string",
                    "required": true,
                    "description": "Enlik dərəcəsi"
                },
                "location[coordinates][longitude]": {
                    "type": "string",
                    "required": true,
                    "description": "Uzunluq dərəcəsi"
                },
                "images[]": {
                    "type": "file",
                    "description": "Şəkillər",
                    "multiple": true,
                    "isArray": true,
                    "accept": "image/*"
                },
                "is_negotiable": {
                    "type": "boolean",
                    "description": "Qiymət danışıq yolu ilə"
                },
                "is_new": {
                    "type": "boolean",
                    "description": "Yeni"
                },
                "is_exchange": {
                    "type": "boolean",
                    "description": "Mübadilə mümkündür"
                },
                "is_credit": {
                    "type": "boolean",
                    "description": "Kreditdədir"
                }
            },
            "responses": {
                "200": {
                    "id": 1,
                    "title": "Satılır 3 otaqlı mənzil (Yenilənmiş)",
                    "slug": "satilir-3-otaqli-menzil-yenilenmis",
                    "description": "Təmirli, işıqlı, mebelli mənzil",
                    "price": 160000,
                    "category_id": 1,
                    "status": "active",
                    "created_at": "2024-01-01 00:00:00",
                    "updated_at": "2024-01-02 00:00:00"
                },
                "422": {
                    "title": "Başlıq mütləqdir",
                    "description": "Təsvir mütləqdir",
                    "price": "Qiymət mütləqdir"
                }
            }
        },
        {
            "path": "/listings/{id}",
            "method": "delete",
            "summary": "Elan silmə",
            "description": "Elanı silir",
            "authorization": true,
            "responses": {
                "200": {
                    "message": "Elan uğurla silindi"
                },
                "404": {
                    "message": "Elan tapılmadı"
                }
            }
        }
    ]
}
```

## ✅ Məhdudiyyətlər və Qaydalar

### Data Tipləri

Standart tiplər:
- `type: "string"` - mətn tipli dəyərlər üçün
- `type: "number"` - ədədi dəyərlər üçün
- `type: "array"` - array dəyərlər üçün
- `type: "object"` - json dəyərlər üçün

FormData ilə istifadə olunan əlavə tiplər:
- `type: "textarea"` - çoxsətirli mətn üçün
- `type: "select"` - seçim siyahısı üçün
- `type: "boolean"` - 0/1 dəyərli məntiq dəyişənləri üçün
- `type: "file"` - fayl yükləmə üçün

### FormData üçün xüsusi parametrlər

- `rows` - textarea üçün sətir sayı
- `options` - select üçün seçim siyahısı
- `multiple` - file tipində çoxlu fayl seçimi üçün
- `isArray` - array tipli dəyərlər üçün
- `accept` - file tipində qəbul edilən fayl növləri üçün

### Məcburi Sahələr
- `tag` - modul adı
- `description` - modul açıqlaması
- `endpoints` - endpoint-lərin siyahısı (array formatında)
- `path` - hər endpoint üçün API yolu
- `method` - hər endpoint üçün HTTP metodu (get, post, put, delete)
- `summary` - hər endpoint üçün qısa başlıq
- `description` - hər endpoint üçün ətraflı açıqlama

### Authorization
- `"authorization": true` - Bearer token tələb edir
- Default olaraq `false`-dır

### FormData
- `"formData": true` - Sorğunu multipart/form-data formatında göndərir
- Default olaraq `false`-dır (JSON formatı istifadə olunur)

### Response Kodları
- `200` - Uğurlu əməliyyat
- `201` - Uğurlu yaradılma
- `404` - Tapılmadı
- `422` - Validasiya xətası
- `500` - Server xətası

## 🚀 İstifadə

1. Swagger qovluğu yaradın
```bash
mkdir swagger
```

2. JSON faylları yaradın (config.json mütləqdir)
```bash
touch swagger/config.json
touch swagger/listings.json
```

3. Dokumentasiyanı generasiya edin
```bash
php artisan doc:generate
```

## 💡 Tövsiyələr

1. Endpoint adları aydın və anlaşılan olmalıdır
2. Hər endpoint üçün detallı açıqlama yazın
3. Validation xətalarını detallı göstərin
4. Default dəyərləri mümkün olan yerlərdə istifadə edin
5. Authorization tələb edən endpoint-ləri qeyd edin
6. Fayl yükləmə endpoint-ləri üçün mütləq `formData: true` istifadə edin
7. Select tipli inputlar üçün bütün mümkün seçimləri `options` array-ində sıralayın
8. Nested struktur və massivlərdə doğru adlandırma konvensiyalarına əməl edin
9. Response nümunələrini real data ilə göstərin
10. Eyni yol (path) üçün müxtəlif HTTP metodlarını ayrı endpoint obyektləri kimi təsvir edin
11. Bütün endpoint-lərin `path` və `method` xüsusiyyətlərini dəqiq qeyd edin

## 🔄 Köhnə və Yeni Format Müqayisəsi

### Köhnə Format (Nested Object Structure)

```json
"endpoints": {
    "/listings": {
        "get": { ... },
        "post": { ... }
    },
    "/listings/{id}": {
        "get": { ... },
        "put": { ... },
        "delete": { ... }
    }
}
```

### Yeni Format (Flattened Array Structure)

```json
"endpoints": [
    {
        "path": "/listings",
        "method": "get",
        ...
    },
    {
        "path": "/listings",
        "method": "post",
        ...
    },
    {
        "path": "/listings/{id}",
        "method": "get",
        ...
    }
]
```

Yeni format daha yaxşı proqram emalını, daha asan iterasiyanı və daha aydın endpoint təsvirini təmin edir. Bu, xüsusilə generasiya prosesini asanlaşdırır və API-lərin daha yaxşı sənədləşdirilməsini təmin edir.

## 📋 Tez-tez verilən suallar (FAQ)

### Fayl yükləməsini necə təyin etməliyəm?

Fayl yükləməsi üçün `formData: true` parametrini təyin edin və fayl sahəsini aşağıdakı kimi göstərin:

```json
"image": {
    "type": "file",
    "description": "Şəkil",
    "accept": "image/*"
}
```

Çoxlu fayl üçün isə:

```json
"images[]": {
    "type": "file",
    "multiple": true,
    "isArray": true,
    "description": "Şəkillər",
    "accept": "image/*"
}
```

### Nested obyektləri necə təyin etməliyəm?

Nested obyektləri kvadrat mötərizə ilə göstərin:

```json
"contact[name]": {
    "type": "string",
    "description": "Əlaqə şəxsinin adı"
},
"contact[email]": {
    "type": "string",
    "description": "Əlaqə e-poçtu"
}
```

Daha dərin strukturlar üçün:

```json
"location[coordinates][latitude]": {
    "type": "string",
    "description": "Enlik dərəcəsi"
},
"location[coordinates][longitude]": {
    "type": "string",
    "description": "Uzunluq dərəcəsi"
}
```

### Dropdown (select) inputlarını necə təyin etməliyəm?

```json
"status": {
    "type": "select",
    "description": "Status",
    "options": [
        {"value": "active", "label": "Aktiv"},
        {"value": "inactive", "label": "Deaktiv"},
        {"value": "pending", "label": "Gözləmədə"}
    ],
    "default": "active"
}
```

Bu yenilənmiş sənədləşdirmə ilə, istifadəçilər FormData ilə işləmək üçün bütün zəruri məlumatları əldə edə biləcəklər. Həmçinin, müxtəlif input növlərinin necə təsvir ediləcəyi və istifadə ediləcəyi barədə ətraflı təlimatlar verilmişdir.
