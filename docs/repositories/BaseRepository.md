# BaseRepository Dokumentasiyası 📚
Mərkəzləşdirilmiş data idarəetmə sinifi

## 📝 Sinif Haqqında
------------------
Base Repository, Laravel layihələrində database əməliyyatlarını idarə edən əsas sinifdir. Bütün digər repository-lər bu sinifi extends edərək baza funksionallıqdan istifadə edə bilir.

## 🔧 Xüsusiyyətlər
----------------
- CRUD əməliyyatları
- Avtomatik keşləmə
- Dinamik filtrləmə
- Səhifələmə (pagination)
- Status idarəetmə
- Soft-delete dəstəyi

## 💻 Metodlar və İstifadəsi
--------------------------

### 🏗️ Konstruktor

```php
public function __construct(Model $model, $useCache = false)
```
Repository-ni initialize edir:
- `$model`: Əsas model instance-ı
- `$useCache`: Keşləmə aktivdir/deaktivdir

### 🔍 Axtarış və Filtrasiya

#### findById()
```php
public function findById(int $id): Model
```
ID-yə görə record axtarır:
- Keşləmə aktiv edilibsə, keşdən məlumat gətirir
- Relation-lar təyin edilibsə, onları da yükləyir
- Tapılmadıqda ModelNotFoundException atır

#### findOneWhere()
```php
public function findOneWhere(array $conditions): ?Model
```
Şərtlərə görə tək record tapır:
```php
// Nümunə istifadə:
$user = $repository->findOneWhere([
    'email' => 'test@example.com',
    'status' => 'active'
]);
```

#### findWhere()
```php
public function findWhere(array $conditions): Collection
```
Şərtlərə görə bütün uyğun recordları tapır:
```php
// Nümunə istifadə:
$activeUsers = $repository->findWhere([
    'status' => 'active',
    'type' => 'admin'
]);
```

### 📝 CRUD Əməliyyatları

#### create()
```php
public function create(array $data): Model
```
Yeni record yaradır və lazım olduqda relations yükləyir:
```php
$user = $repository->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

#### update()
```php
public function update(int $id, array $data): Model
```
Mövcud recordu yeniləyir:
- Keşi avtomatik təmizləyir
- Relations varsa yeniləyir

#### delete()
```php
public function delete(int $id): bool
```
ID-yə görə record silir:
- Keşi avtomatik təmizləyir
- Soft-delete aktivdirsə soft-delete edir

### 🔄 Bulk Əməliyyatlar

#### updateWhere()
```php
public function updateWhere(array $conditions, array $data): bool
```
Şərtlərə uyğun çoxlu record yeniləyir:
```php
$repository->updateWhere(
    ['status' => 'pending'],
    ['status' => 'processed']
);
```

#### deleteWhere()
```php
public function deleteWhere(array $conditions): bool
```
Şərtlərə uyğun recordları silir.

### 📊 Səhifələmə və Filtrasiya

#### paginateAndFilter()
```php
public function paginateAndFilter(): LengthAwarePaginator
```
Filterlənmiş və səhifələnmiş data qaytarır.

Dəstəklənən filter parametrləri:
- `search`: Ümumi axtarış
- `status`: Status filtri
- `is_active`: Aktivlik filtri
- `date_range`: Tarix aralığı
- `trashed`: Silinmiş recordlar
- `sort_by`: Sıralama sahəsi
- `sort_direction`: Sıralama istiqaməti

```php
// URL: /users?search=john&status=active&sort_by=created_at&sort_direction=desc
$users = $repository->paginateAndFilter();
```

### 🎯 Status İdarəetmə

#### changeStatus()
```php
public function changeStatus(int $id, string $statusField = 'is_active'): Model
```
Status sahəsinin dəyərini dəyişir:
```php
$user = $repository->changeStatus(1, 'is_verified');
```

### 📋 Relation İdarəetmə

#### with()
```php
public function with(array $relations): self
```
Eager loading üçün relation-ları təyin edir:
```php
$users = $repository
    ->with(['profile', 'posts'])
    ->findWhere(['status' => 'active']);
```

### 🔍 Filter İdarəetmə

#### setFilter()
```php
public function setFilter(FilterInterface $filter): self
```
Xüsusi filter class-ı təyin edir:
```php
$users = $repository
    ->setFilter(new UserFilter(request()))
    ->paginateAndFilter();
```

## ⚙️ Konfiqurasiya
-----------------

### pagination.php
```php
return [
    'limits' => [
        'default' => 20,
        'max' => 100
    ],
    'sorting' => [
        'default' => [
            'field' => 'id',
            'direction' => 'desc'
        ]
    ]
];
```

### filters.php
```php
return [
    'searchable' => [
        'default' => ['name']
    ]
];
```

## 📤 API Response Format
----------------------
`paginateAndFilter()` metodu aşağıdakı formatda data qaytarır:

```json
{
    "data": [], 
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150,
        "has_more": true
    },
    "links": {
        "first": "/?page=1",
        "last": "/?page=10",
        "prev": null,
        "next": "/?page=2"
    },
    "filters": {
        "search": "john",
        "status": "active",
        "date_range": {
            "from": "2024-01-01",
            "to": "2024-12-31"
        }
    }
}
```

Bu dokumentasiya BaseRepository sinifinin bütün funksionallığını əhatə edir. Hər metod üçün nümunələr və izahlar əlavə edilib. Sinifdə olan bütün public metodlar və onların istifadəsi detallı şəkildə açıqlanıb.
