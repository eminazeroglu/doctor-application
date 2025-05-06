# HasImage Trait Documentation

## Overview
`HasImage` trait, Laravel modelləriniz üçün şəkil yükləmə, silmə və idarəetmə funksionallığını təmin edən bir mexanizmdir. Bu trait, şəkillərin avtomatik yüklənməsi, ölçüləndirilməsi və silinməsi kimi əməliyyatları avtomatlaşdırır.

## Xüsusiyyətlər
- Avtomatik şəkil yükləmə və silmə
- Base64 və UploadedFile dəstəyi
- Çoxlu ölçü variantları (thumbnail, medium, large)
- Hər model üçün fərqli şəkil konfiqurasiyası
- Avtomatik şəkil adı generasiyası

## Quraşdırma
1. Traiti modelinizə əlavə edin və `getImageFields` metodunu implement edin:

```php
use App\Traits\Model\HasImage;

class Product extends Model
{
    use HasImage;

    public function getImageFields(): array
    {
        return [
            'image' => [
                'path' => 'products',
                'thumbnail' => [100, 100],
                'medium' => [300, 300],
                'large' => [600, 600]
            ]
        ];
    }
}
```

## Konfiqurasiya
`getImageFields` metodu vasitəsilə hər şəkil sahəsi üçün konfiqurasiya təyin edə bilərsiniz:

```php
public function getImageFields(): array
{
    return [
        'image' => [                    // database sütununun adı
            'path' => 'products',       // yükləmə qovluğu
            'thumbnail' => [100, 100],  // thumbnail ölçüsü
            'medium' => [300, 300],     // medium ölçü
            'large' => [600, 600]       // large ölçü
        ],
        'cover' => [                    // başqa bir şəkil sahəsi
            'path' => 'products/covers',
            'thumbnail' => [200, 100]
        ]
    ];
}
```

## İstifadə Qaydaları

### 1. Şəkil Yükləmə
```php
// UploadedFile ilə
$product = Product::create([
    'name' => 'Test Product',
    'image' => $request->file('image')
]);

// Base64 ilə
$product->setUseBase64(true)->update([
    'image' => $base64String
]);
```

### 2. Şəkil URL-i Əldə Etmə
```php
// Original ölçü
$url = $product->getImageUrl('image');

// Specific ölçü
$thumbnailUrl = $product->getImageUrl('image', 'thumbnail');
$mediumUrl = $product->getImageUrl('image', 'medium');
$largeUrl = $product->getImageUrl('image', 'large');
```

## Metodlar

### Public Metodlar
- `setUseBase64()`: Base64 istifadəsini aktivləşdirir/deaktivləşdirir
- `getImageFields()`: Şəkil sahələrinin konfiqurasiyasını qaytarır
- `getImageUrl()`: Şəklin URL-ni qaytarır
- `uploadImages()`: Şəkilləri yükləyir
- `deleteImages()`: Şəkilləri silir

### Protected Metodlar
- `isImageFieldUpdated()`: Şəkil sahəsinin yenilənib-yenilənmədiyini yoxlayır
- `getImageFile()`: Request və ya attributesdan şəkil faylını əldə edir
- `generateImageName()`: Unikal şəkil adı yaradır

## Events
Trait aşağıdakı model eventlərini avtomatik idarə edir:
- `saving`: Şəkilləri yükləyir
- `deleting`: Şəkilləri silir

## Tövsiyələr
- Şəkil sahələri üçün nullable database sütunları yaradın
- Böyük şəkillər üçün queue istifadə edin
- Disk quotasını nəzərə alın
- Şəkil tiplərinə limit qoyun
- Şəkil ölçülərinə limit qoyun

## Texniki Detallar
- PHP 8.2+ tələb olunur
- Laravel Eloquent Model ilə işləyir
- Intervention/Image package istifadə edir
- Base64 və UploadedFile dəstəkləyir

## Nümunələr

### Multiple Şəkil Sahələri
```php
class Product extends Model
{
    use HasImage;

    public function getImageFields(): array
    {
        return [
            'thumbnail' => [
                'path' => 'products/thumbnails',
                'thumbnail' => [100, 100]
            ],
            'main_image' => [
                'path' => 'products/main',
                'medium' => [400, 400],
                'large' => [800, 800]
            ]
        ];
    }
}
```

### Base64 İstifadəsi
```php
$product->setUseBase64(true)->update([
    'image' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg...'
]);
```

## Qeydlər
- Şəkillər avtomatik olaraq storage/app/public qovluğuna yüklənir
- Hər ölçü üçün ayrı fayl yaradılır
- Orijinal şəkil həmişə saxlanılır
- Model silindikdə bütün şəkillər avtomatik silinir
