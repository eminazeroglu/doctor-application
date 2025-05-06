# HasCode Trait Documentation

## Overview
`HasCode` trait, modelləriniz üçün avtomatik unikal kod generasiyası təmin edən bir mexanizmdir. Bu trait, hər bir model üçün avtomatik olaraq unikal alfanumerik kodlar yaradır və idarə edir.

## Xüsusiyyətlər
- Avtomatik unikal kod generasiyası
- Konfiqurasiya edilə bilən kod uzunluğu
- Konfiqurasiya edilə bilən kod sahəsi adı
- Model axtarışı üçün hazır metodlar

## Quraşdırma
1. Traiti modelinizə əlavə edin:

```php
use App\Traits\Model\HasCode;

class YourModel extends Model
{
    use HasCode;
}
```

## Konfiqurasiya
Modelinizdə aşağıdakı xüsusiyyətləri təyin edə bilərsiniz:

```php
protected $codeField = 'code'; // Kod sahəsinin adı (default: 'code')
protected $codeLength = 6;     // Kodun uzunluğu (default: 6)
```

## İstifadə Qaydaları

### 1. Avtomatik Kod Generasiyası
Model yaradıldıqda və ya yadda saxlanıldıqda kod avtomatik generasiya olunur:
```php
$model = YourModel::create([
    'name' => 'Test Model'
]); // code sahəsi avtomatik doldurulacaq
```

### 2. Model Axtarışı
Koda görə model axtarışı:
```php
// Metod 1: findByCode istifadəsi
$model = YourModel::findByCode('ABC123');

// Metod 2: whereCode scope istifadəsi
$model = YourModel::whereCode('ABC123')->first();
```

## Metodlar

### Public Metodlar
- `getCodeFieldName()`: Kod sahəsinin adını qaytarır
- `generateUniqueCode()`: Yeni unikal kod yaradır
- `scopeWhereCode()`: Koda görə axtarış üçün scope
- `findByCode()`: Koda görə model axtarışı üçün statik metod

### Protected Metodlar
- `generateCode()`: Müəyyən uzunluqda kod yaradır
- `codeExists()`: Kodun mövcudluğunu yoxlayır
- `getCodeLength()`: Kod uzunluğunu qaytarır

## Kod Formatı
- Kodlar böyük hərflər (A-Z) və rəqəmlərdən (0-9) ibarətdir
- Default uzunluq 6 simvoldur
- Hər kod unikaldır

## Nümunələr

### Basic İstifadə
```php
class Product extends Model
{
    use HasCode;
}

// Yeni məhsul yaratdıqda
$product = Product::create([
    'name' => 'Test Product'
]); // avtomatik kod generasiya olunacaq

// Koda görə məhsul axtarışı
$product = Product::findByCode('ABC123');
```

### Xüsusi Konfiqurasiya ilə
```php
class Order extends Model
{
    use HasCode;
    
    protected $codeField = 'order_number';
    protected $codeLength = 8;
}
```

## Qeydlər
- Kod generasiyası `creating` və `saving` eventlərində baş verir
- Əgər kod əl ilə təyin edilibsə, yeni kod generasiya olunmayacaq
- Bütün kodlar unikaldır və təkrarlanmır

## Tövsiyələr
- Kod sahəsini database-də `unique` index ilə yaradın
- Performans üçün kod sahəsini indeksləyin
- Böyük həcmli datalar üçün kod uzunluğunu artırın

## Texniki Detallar
- PHP 8.2+ tələb olunur
- Laravel Eloquent Model ilə işləyir
- Trait avtomatik olaraq model eventlərini qeydiyyatdan keçirir
