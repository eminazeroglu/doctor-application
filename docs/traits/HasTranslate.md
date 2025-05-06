# HasTranslate Trait Documentation

## Ümumi Baxış
`HasTranslate` trait-i Laravel modelləriniz üçün çoxdilli kontenti idarə etməyə imkan verən güclü bir alətdir. Bu trait, modellərinizin tərcümə oluna bilən atributlarını asanlıqla idarə etməyə imkan verir.

## Xüsusiyyətlər
- Avtomatik JSON formatında tərcümələrin saxlanması
- Cari dilə əsasən tərcümələrin əldə edilməsi
- Dinamik atribut əldə etmə və təyin etmə
- Global scope vasitəsilə tərcümələrin avtomatik əlavə edilməsi
- Çevik və konfiqurasiya edilə bilən struktur

## Quraşdırma
1. Traiti modelinizə əlavə edin:

```php
use App\Traits\Model\HasTranslate;

class Product extends Model
{
    use HasTranslate;

    public function getTranslatableAttributes(): array
    {
        return ['name', 'description'];
    }
}
```

## Database Konfiqurasiyası
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->json('translates')->nullable();
    $table->timestamps();
});
```

## İstifadə Qaydaları

### 1. Tərcümə Əlavə Etmək
```php
$product = Product::create([
    'translates' => [
        'az' => [
            'name' => 'Məhsul',
            'description' => 'Məhsul haqqında'
        ],
        'en' => [
            'name' => 'Product',
            'description' => 'About product'
        ]
    ]
]);
```

### 2. Tərcüməni Əldə Etmək
```php
// Cari dildəki tərcüməni əldə etmək
$product->name; // Cari dildəki adı qaytarır

// Konkret dildəki tərcüməni əldə etmək
$product->getTranslation('name', 'en'); // 'Product'
```

### 3. Tərcüməni Yeniləmək
```php
$product->setTranslation('name', 'Yeni Məhsul', 'az');
$product->save();
```

## Avtomatik İşləmə
Trait aşağıdakı hallarda avtomatik işləyir:

1. Model yaradılanda
2. Model yeniləndikdə
3. Atributlar əldə ediləndə və ya təyin ediləndə

## Metodlar

### Public Metodlar
- `getTranslation($key, $locale = null)`: Konkret dildəki tərcüməni qaytarır
- `setTranslation($key, $value, $locale = null)`: Konkret dildə tərcüməni təyin edir
- `getTranslatableAttributes()`: Tərcümə edilə bilən sahələrin siyahısını qaytarır

## Texniki Tələblər
- PHP 8.0+
- Laravel 8.0+
- JSON sütun dəstəyi olan verilənlər bazası

## Qeydlər və Tövsiyələr
- Tərcümələr JSON formatında saxlanılır
- Cari dil `Helper::language()` metodu ilə təyin olunur
- Mövcud olmayan tərcümələr üçün `null` qaytarılır
- Model array-ə çevriləndə tərcümələr avtomatik əlavə olunur

## Nümunə Model
```php
class Product extends Model
{
    use HasTranslate;

    protected $fillable = ['translates'];

    public function getTranslatableAttributes(): array
    {
        return [
            'name',
            'description',
            'meta_title',
            'meta_description'
        ];
    }
}
```
