# HasSlug Trait Documentation

## Overview
`HasSlug` trait Laravel modelləriniz üçün avtomatik URL-friendly slug generasiyası təmin edən bir mexanizmdir. Bu trait həm tək dilli, həm də çoxdilli modellərdə slug yaratmağı və idarə etməyi avtomatlaşdırır. Çoxdilli modellərdə hər dil üçün ayrı slug yarada bilir.

## Xüsusiyyətlər
- Avtomatik slug generasiyası
- Çoxdilli dəstək (hər dil üçün ayrı slug)
- Unikal slug təminatı
- Model yaradılma və yenilənmə zamanı avtomatik işləmə
- Konfiqurasiya edilə bilən mənbə və hədəf sahələri
- Dil-spesifik slug əldə etmə imkanı

## Quraşdırma

### 1. Tək dilli model üçün:

```php
use App\Traits\Model\HasSlug;

class Product extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug'
    ];
}
```

### 2. Çoxdilli model üçün:

```php
use App\Traits\Model\HasSlug;use App\Traits\Model\HasTranslate;

class Category extends Model
{
    use HasSlug, HasTranslate;

    protected $fillable = [
        'slug',
        'translates'
    ];

    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'slug'
        ];
    }
}
```

## Konfiqurasiya

### Default Konfiqurasiya
- Mənbə sahəsi: `name`
- Hədəf sahəsi: `slug`
- Default dil: `app.fallback_locale` (config-dən)

### Konfiqurasiya Parametrləri
```php
class Product extends Model
{
    use HasSlug;

    // Mənbə sahəsini dəyişmək
    protected $slugSource = 'title';

    // Hədəf sahəsini dəyişmək
    protected $slugField = 'url';
}
```

## İstifadə Qaydaları

### 1. Tək Dilli Model
```php
$product = Product::create([
    'name' => 'Test Product'
]); 
// slug: "test-product"
```

### 2. Çoxdilli Model
```php
$category = Category::create([
    'translates' => [
        'az' => ['name' => 'Test Məhsul'],
        'en' => ['name' => 'Test Product'],
        'ru' => ['name' => 'Тестовый продукт']
    ]
]); 

// Nəticə:
// əsas slug: "test-mehsul"
// translates -> az -> slug: "test-mehsul"
// translates -> en -> slug: "test-product"
// translates -> ru -> slug: "testovyy-produkt"
```

### 3. Slug Əldə Etmə
```php
// Əsas slug
$category->slug;

// Konkret dildəki slug
$category->getSlugByLang('en');

// Bütün dillərdəki sluglar
$category->getAllSlugs();
```

## Database Konfiqurasiya

### 1. Tək Dilli Model
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->timestamps();
});
```

### 2. Çoxdilli Model
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->json('translates');
    $table->timestamps();
});
```

## Repository İstifadəsi

```php
// Normal slug ilə axtarış
$model = $repository->findBySlug('my-slug');

// Dil-spesifik slug ilə axtarış
$model = $repository->findBySlug('menim-slugim', 'az');
// və ya
$model = $repository->findByLocalizedSlug('my-slug', 'en');
```

## Service İstifadəsi

```php
// Normal slug ilə axtarış
$model = $service->findBySlug('my-slug');

// Dil-spesifik slug ilə axtarış
$model = $service->findBySlug('menim-slugim', 'az');
// və ya
$model = $service->findByLocalizedSlug('my-slug', 'en');
```

## Route Model Binding

```php
class Category extends Model
{
    use HasSlug;

    public function getRouteKeyName()
    {
        return 'slug';
    }
}

// Route faylında
Route::get('categories/{category:slug}', [CategoryController::class, 'show']);
```

## Texniki Spesifikasiyalar
- PHP 8.2+
- Laravel 10+
- JSON tipli translates sahəsi dəstəyi
- Laravel Str::slug() istifadəsi
- Avtomatik unikal slug generasiyası

## Önəmli Qeydlər
- Sluglar avtomatik olaraq unikal yaradılır
- Təkrarlanan sluglar üçün nömrələmə (test, test-2, test-3)
- Çoxdilli modellərdə hər dil üçün ayrı slug yaradılır
- Default dildəki slug əsas slug sahəsinə də yazılır
- Boş mənbə dəyərləri üçün slug yaradılmır
- İstənilən dildəki slug ilə axtarış mümkündür

## Tövsiyələr
- Slug sahəsini həmişə unique index ilə yaradın
- Çoxdilli modellər üçün translates sahəsini json tipində yaradın
- Repository və Service səviyyəsində findBySlug metodlarından istifadə edin
- URL-lərdə route model binding istifadə edin
