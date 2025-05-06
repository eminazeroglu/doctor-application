# ImageUploadService

## Təsvir

ImageUploadService Laravel layihələrində şəkillərin yüklənməsi və emalı üçün xüsusi servisidir. Servis Intervention Image v3 kitabxanası üzərində qurulub və həm lokal, həm də S3 storage sistemləri ilə işləyə bilir.

## Əsas Xüsusiyyətlər

- Şəkillərin yüklənməsi (file, base64, url)
- Şəkil ölçüsünün dəyişdirilməsi
- Watermark əlavə edilməsi
- Müxtəlif versiyaların (thumbnail, medium, large) yaradılması
- Şəkil fırladılması
- Köhnə şəkillərin silinməsi

## Quraşdırma

### Tələb olunan paketlər

```json
{
    "intervention/image": "^3.0",
    "laravel/framework": "^10.0"
}
```

### Sistem tələbləri

- PHP >= 8.1
- GD Library
- Fileinfo Extension

## Dəyişənlər

```php
private ImageManager $manager;
private string $driver;
private string $mainFolder = '/uploads/photos';
private ?string $name = null;
private ?string $path = null;
private array $formats = ['jpg', 'jpeg', 'png', 'gif'];
private bool $base64 = false;
private bool $url = false;
private int $quality = 85;
private ?array $size = null;
private array $watermark = [];
private ?array $thumbnail = null;
private ?array $medium = null;
private ?array $large = null;
private $file;
private ?string $remove = null;
private ?int $rotate = null;
```

## Metodlar

### Constructor və Inisiallizasiya

#### __construct()
```php
public function __construct()
```
- **Təsvir**: Servisin inisializasiyası
- **Funksionallıq**:
    - ImageManager yaradır
    - Default driver təyin edir
    - Upload qovluğunu yaradır

#### createUploadPath()
```php
private function createUploadPath(): void
```
- **Təsvir**: Upload qovluğunu yaradır
- **Funksionallıq**:
    - S3 və ya lokal storage üçün qovluq yaradır
    - Qovluq mövcud deyilsə onu yaradır

### Setter Metodlar

#### setFile()
```php
public function setFile($file): self
```
- **Parametrlər**: `$file` - UploadedFile|string
- **Return**: self
- **Təsvir**: Yüklənəcək faylı təyin edir

#### setName()
```php
public function setName(string $val): self
```
- **Parametrlər**: `$val` - string
- **Return**: self
- **Təsvir**: Fayl adını təyin edir

#### setPath()
```php
public function setPath(string $val): self
```
- **Parametrlər**: `$val` - string
- **Return**: self
- **Təsvir**: Yükləmə qovluğunu təyin edir

#### setRemoveFile()
```php
public function setRemoveFile(?string $name): self
```
- **Parametrlər**: `$name` - string|null
- **Return**: self
- **Təsvir**: Silinəcək köhnə faylı təyin edir

#### setFormats()
```php
public function setFormats(array $val): self
```
- **Parametrlər**: `$val` - array
- **Return**: self
- **Təsvir**: İcazə verilən formatları təyin edir

#### setBase64()
```php
public function setBase64(bool $val): self
```
- **Parametrlər**: `$val` - bool
- **Return**: self
- **Təsvir**: Base64 formatında yükləməni aktivləşdirir

#### setUrl()
```php
public function setUrl(bool $val): self
```
- **Parametrlər**: `$val` - bool
- **Return**: self
- **Təsvir**: URL-dən yükləməni aktivləşdirir

#### setQuality()
```php
public function setQuality(int $val = 85): self
```
- **Parametrlər**: `$val` - int
- **Return**: self
- **Təsvir**: Şəkil keyfiyyətini təyin edir

#### setRotate()
```php
public function setRotate(int $val): self
```
- **Parametrlər**: `$val` - int
- **Return**: self
- **Təsvir**: Şəklin fırladılma dərəcəsini təyin edir

#### setSize()
```php
public function setSize(int $width, int $height): self
```
- **Parametrlər**:
    - `$width` - int
    - `$height` - int
- **Return**: self
- **Təsvir**: Şəklin ölçülərini təyin edir

#### setWatermark()
```php
public function setWatermark(
    string $text, 
    int $size = 20, 
    string $color = '#ffffff', 
    string $position = 'bottom-right', 
    int $x = 10, 
    int $y = 10
): self
```
- **Parametrlər**:
    - `$text` - Watermark mətni
    - `$size` - Şrift ölçüsü
    - `$color` - Rəng kodu
    - `$position` - Mövqe
    - `$x` - X koordinatı
    - `$y` - Y koordinatı
- **Return**: self
- **Təsvir**: Watermark parametrlərini təyin edir

#### setThumbnail()
```php
public function setThumbnail(int $width, int $height): self
```
- **Parametrlər**:
    - `$width` - int
    - `$height` - int
- **Return**: self
- **Təsvir**: Thumbnail ölçülərini təyin edir

#### setMedium()
```php
public function setMedium(int $width, int $height): self
```
- **Parametrlər**:
    - `$width` - int
    - `$height` - int
- **Return**: self
- **Təsvir**: Orta ölçülü versiya parametrlərini təyin edir

#### setLarge()
```php
public function setLarge(int $width, int $height): self
```
- **Parametrlər**:
    - `$width` - int
    - `$height` - int
- **Return**: self
- **Təsvir**: Böyük ölçülü versiya parametrlərini təyin edir

### Əsas Funksionallıq Metodları

#### upload()
```php
public function upload(): ?string
```
- **Return**: string|null
- **Təsvir**: Şəkli yükləyir və emal edir
- **Funksionallıq**:
    - Şəkli yaradır
    - Format yoxlaması edir
    - Fayl adı generasiya edir
    - Şəkli emal edir və saxlayır
    - Əlavə versiyaları yaradır
    - Köhnə faylı silir

#### getPhoto()
```php
public function getPhoto(
    string $path, 
    string $name, 
    string $defaultPhoto = 'default_photo.webp'
): array
```
- **Parametrlər**:
    - `$path` - Şəklin yolu
    - `$name` - Fayl adı
    - `$defaultPhoto` - Default şəkil
- **Return**: array
- **Təsvir**: Şəkil URL-lərini qaytarır (original və bütün versiyalar)

#### delete()
```php
public function delete(string $path, string $name): bool
```
- **Parametrlər**:
    - `$path` - Şəklin yolu
    - `$name` - Fayl adı
- **Return**: bool
- **Təsvir**: Şəkli və onun bütün versiyalarını silir

### Köməkçi Metodlar

#### createImage()
```php
private function createImage(): ImageInterface
```
- **Return**: ImageInterface
- **Təsvir**: Şəkil obyekti yaradır (file, base64, url)

#### getExtension()
```php
private function getExtension(ImageInterface $image): string
```
- **Return**: string
- **Təsvir**: Şəklin formatını təyin edir

#### generateName()
```php
private function generateName(string $extension): string
```
- **Return**: string
- **Təsvir**: Unikal fayl adı generasiya edir

#### processImage()
```php
private function processImage(ImageInterface $image, string $filePath): void
```
- **Təsvir**: Şəkli emal edir (ölçü, fırlatma, watermark)

#### createResizedVersions()
```php
private function createResizedVersions(ImageInterface $image): void
```
- **Təsvir**: Şəklin müxtəlif ölçülü versiyalarını yaradır

#### ensureDirectoryExists()
```php
private function ensureDirectoryExists(string $filePath): void
```
- **Təsvir**: Qovluğun mövcudluğunu yoxlayır və yaradır

#### saveImage()
```php
private function saveImage(ImageInterface $image, string $filePath, ?string $format = null): void
```
- **Təsvir**: Şəkli disk/storage-də saxlayır

#### getEncoder()
```php
private function getEncoder(string $format): JpegEncoder|PngEncoder|WebpEncoder|GifEncoder
```
- **Təsvir**: Format üçün müvafiq encoder qaytarır

## İstifadə Nümunələri

### Sadə şəkil yükləmə
```php
$imageService = new ImageUploadService();
$fileName = $imageService
    ->setFile($request->file('image'))
    ->setPath('users/avatars')
    ->upload();
```

### Watermark ilə yükləmə
```php
$fileName = $imageService
    ->setFile($request->file('image'))
    ->setPath('products')
    ->setWatermark('Copyright 2024')
    ->setQuality(90)
    ->upload();
```

### Bütün versiyalarla yükləmə
```php
$fileName = $imageService
    ->setFile($request->file('image'))
    ->setPath('gallery')
    ->setThumbnail(150, 150)
    ->setMedium(400, 400)
    ->setLarge(800, 800)
    ->upload();
```

## Qeydlər və Məhdudiyyətlər

### Dəstəklənən formatlar
- JPG/JPEG
- PNG
- GIF

### Storage sistemləri
- Local disk
- Amazon S3

### Vacib qeydlər
1. Bütün yüklənən şəkillərin path-i təyin edilməlidir
2. Versiya şəkilləri həmişə WebP formatında saxlanılır
3. Default keyfiyyət: 85%
4. S3 istifadəsi üçün düzgün konfiqurasiya tələb olunur

### Məhdudiyyətlər
1. Yalnız təyin edilmiş formatlarda fayllar qəbul edilir
2. Watermark yalnız mətn formatında ola bilər
3. Local storage istifadəsi zamanı qovluqlar public_path() daxilində yaradılır

## Xətalar

Servis xəta baş verdiyi halda null qaytarır. Əsas xəta halları:
- Fayl təyin edilməyib
- Format dəstəklənmir
- Fayl oxuna bilmir
- Qovluq yaradıla bilmir
