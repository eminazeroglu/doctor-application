# HasUuid Trait Documentation

## Ümumi Baxış
`HasUuid` trait-i Laravel modelləriniz üçün avtomatik UUID (Universally Unique Identifier) generasiyası və idarəetməsini təmin edən bir mexanizmdir. Bu trait, modellərinizə təhlükəsiz və unikal identifikatorlar əlavə etməyə imkan verir.

## Xüsusiyyətlər
- Avtomatik UUID generasiyası
- Route model binding dəstəyi
- UUID əsasında axtarış metodları
- Model yaradılma zamanı avtomatik işləmə
- Sadə və effektiv implementasiya

## Quraşdırma
1. Traiti modelinizə əlavə edin:

```php
use App\Traits\Model\HasUuid;

class User extends Model
{
    use HasUuid;
}
```

## Database Konfiqurasiyası
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->timestamps();
});
```

## İstifadə Qaydaları

### 1. Avtomatik UUID Generasiyası
```php
$user = User::create([
    'name' => 'John Doe'
]); // uuid avtomatik generasiya olunur
```

### 2. UUID ilə Axtarış
```php
// UUID ilə model axtarışı
$user = User::findByUuid('550e8400-e29b-41d4-a716-446655440000');

// UUID ilə sorğu
$user = User::whereUuid('550e8400-e29b-41d4-a716-446655440000')->first();
```

### 3. Route Model Binding
```php
// web.php və ya api.php
Route::get('/users/{user}', function (User $user) {
    return $user;
}); // UUID əsasında avtomatik model tapılır
```

## Metodlar

### Public Metodlar
- `getRouteKeyName()`: Route model binding üçün açar adını qaytarır
- `scopeWhereUuid()`: UUID əsasında sorğu yaradır
- `findByUuid()`: UUID əsasında model axtarır

### Protected Metodlar
- `bootHasUuid()`: UUID generasiyasını idarə edir

## Texniki Tələblər
- PHP 8.0+
- Laravel 8.0+
- UUID sütunu olan verilənlər bazası

## Qeydlər və Tövsiyələr
- UUID sütununu `unique` index ilə yaradın
- UUID-ni əsas açar (primary key) kimi istifadə etməyin
- Route model binding üçün UUID istifadə edin
- UUID uzunluğunu 36 simvol olaraq təyin edin

## Nümunə Model
```php
class User extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'email'
    ];

    // UUID avtomatik generasiya olunacaq
}
```

## UUID-nin Üstünlükləri
- Qlobal unikallıq
- Təhlükəsizlik (ID-lərin təxmin edilməsinin qarşısını alır)
- Distributed sistemlərdə effektivlik
- Paralel generasiya zamanı konfliktlərin olmaması

## Texniki Detallar
- UUID v4 standartından istifadə edir
- Laravel Str::uuid() funksiyasından istifadə edir
- 36 simvolluq string formatında saxlanılır
- Avtomatik generasiya model yaradılarkən baş verir
