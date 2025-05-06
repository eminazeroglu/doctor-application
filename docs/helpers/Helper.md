# Helper

## 🎯 Əsas Məqsəd

* Tez-tez istifadə edilən funksiyaları mərkəzləşdirilmiş şəkildə təmin edir
* Utilty funksiyaları və formatlamaları asanlaşdırır
* Təkrarlanan kodları azaldır

## 🚀 Sürətli Başlanğıc

~~~php
// Dil əldə etmək
$currentLang = Helper::language();

// Slug yaratmaq
$slug = Helper::createSlug(Product::class, 'Məhsul adı');

// Qiymət formatlamaq
$formattedPrice = Helper::formatPrice(99.99);
~~~

## 📋 Əsas Funksiyalar

### 1. Language Management
~~~php
// Cari dili əldə etmək
$lang = Helper::language();
// Header -> Session -> Default sırası ilə yoxlayır
~~~

### 2. URL və Slug Funksiyaları
~~~php
// Qısa URL yaratmaq
$shortUrl = Helper::shortUrl(Product::class);  // "abc1234"

// Slug yaratmaq
$slug = Helper::createSlug(Post::class, 'Məqalə Başlığı');  // "meqale-basliqi"
~~~

### 3. Şifrələmə və Deşifrələmə
~~~php
// Mətn şifrələmək
$encrypted = Helper::encrypt('Gizli məlumat');

// Şifrəni açmaq
$decrypted = Helper::decrypt($encrypted);
~~~

## 🔧 Funksiyalar və İstifadəsi

### language()
* ✨ Cari dil kodunu qaytarır
* 📤 `string`
* ⚡️ Nümunə:
~~~php
$lang = Helper::language(); // "az", "en", etc.
~~~

### shortUrl()
* ✨ Qısa URL yaradır
* 📥 `modelClass`, `field`
* 📤 `string`
* ⚡️ Nümunə:
~~~php
$url = Helper::shortUrl(Product::class); // "xyz9876"
~~~

### createSlug()
* ✨ SEO-friendly slug yaradır
* 📥 `modelClass`, `text`, `field`
* 📤 `string`
* ⚡️ Nümunə:
~~~php
$slug = Helper::createSlug(Post::class, 'Yeni Məqalə'); // "yeni-meqale"
~~~

### maskString()
* ✨ Mətnin bir hissəsini gizlədir
* 📥 `text`, `visibleStart`, `visibleEnd`
* 📤 `string`
* ⚡️ Nümunə:
~~~php
$masked = Helper::maskString('1234567890', 2, 2); // "12******90"
~~~

### formatPrice()
* ✨ Qiyməti formatlaşdırır
* 📥 `price`, `currency`
* 📤 `string`
* ⚡️ Nümunə:
~~~php
$price = Helper::formatPrice(99.99); // "99.99 ₼"
~~~

## ⚠️ Vacib Qeydlər

1. Şifrələmə:
    * `encrypt()` və `decrypt()` Laravel-in Crypt facade istifadə edir
    * DecryptException halında boş string qaytarır

2. Fayl ölçüsü:
    * B, KB, MB, GB, TB, PB, EB, ZB, YB vahidlərini dəstəkləyir
    * Avtomatik ən uyğun vahidi seçir

3. Unikal identifikator:
    * Rəqəm və ya string ola bilər
    * Təkrarlanmayan olmasını yoxlayır

## 💡 İstifadə Nümunələri

### URL və Slug İşləmləri
~~~php
// Qısa URL
$shortUrl = Helper::shortUrl(Product::class, 'code');

// Slug
$slug = Helper::createSlug(Post::class, 'Məqalə başlığı');
~~~

### Fayl Əməliyyatları
~~~php
// Fayl ölçüsü
$size = Helper::fileSize('path/to/file.pdf'); // "2.5 MB"
~~~

### Təhlükəsizlik
~~~php
// Şifrələmə
$encrypted = Helper::encrypt('sensitive_data');
$decrypted = Helper::decrypt($encrypted);

// Maskalama
$masked = Helper::maskString('user@email.com', 2, 4); // "us*****l.com"
~~~

### Unikal İdentifikatorlar
~~~php
// Rəqəmli kod
$numericCode = Helper::generateUniqueIdentifier(
    Order::class,
    'order_number',
    'number',
    8
);

// String kod
$stringCode = Helper::generateUniqueIdentifier(
    Product::class,
    'sku',
    'string',
    6
);
~~~

### Qiymət Formatlaması
~~~php
$price = Helper::formatPrice(1299.99); // "1,299.99 ₼"
$price = Helper::formatPrice(99.99, '$'); // "99.99 $"
~~~

### Nömrə Generasiyası
~~~php
$number = Helper::generateNumber(6); // "123456"
~~~
