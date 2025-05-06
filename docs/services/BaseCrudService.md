# BaseCrudService

## 🎯 Əsas Məqsəd

* Laravel layihələrində CRUD (Create, Read, Update, Delete) əməliyyatları üçün base service class
* Repository pattern istifadə edərək data layer ilə business logic arasında abstraction təmin edir
* Controller-ləri təmiz saxlamaq və kod təkrarını azaltmaq üçün istifadə olunur

## 🚀 Sürətli Başlanğıc

```php
// UserService yaratmaq üçün BaseCrudService-dən extend edin
class UserService extends BaseCrudService 
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}

// Controller-də istifadəsi
public function store(UserRequest $request, UserService $service)
{
    $user = $service->create($request->validated());
}
```

## 📋 Əsas İstifadə Halları

### 1. Yeni Service Class Yaratmaq

```php
// ProductService.php
class ProductService extends BaseCrudService 
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }
    
    // Əlavə business logic metodları əlavə edə bilərsiniz
    public function calculateDiscount($id, $percentage)
    {
        $product = $this->findById($id);
        // Endirimlə bağlı əməliyyatlar
    }
}
```

* Hər bir model üçün ayrı service yaratdıqda istifadə edin
* Business logic-i repository-dən ayırmaq üçün ideal

### 2. DataTable ilə İnteqrasiya

```php
// ProductController.php
public function index(ProductService $service)
{
    return $service->paginateAndFilter();
}

// Vue/React komponentində
const products = await axios.get('/api/products');
```

* Frontend-də cədvəl və ya list görüntüləmək üçün
* Səhifələmə və filtrləmə lazım olan hər yerdə

## 🔧 Metodlar və İstifadəsi

### create(array $data)

* ✨ Yeni resurs yaradır
* 📥 `$data`: Yaradılacaq resursa aid məlumatlar array formasında
* 📤 Yaradılan model instance-ı qaytarır
* ⚡️ Nümunə:

```php
$product = $productService->create([
    'name' => 'iPhone 13',
    'price' => 1999.99,
    'is_active' => true
]);
```

### update(int $id, array $data)

* ✨ Mövcud resursu yeniləyir
* 📥 `$id`: Resursun ID-si, `$data`: Yenilənəcək məlumatlar
* 📤 Yenilənmiş model instance-ı
* ⚡️ Nümunə:

```php
$updatedUser = $userService->update($id, [
    'email' => 'new@email.com',
    'name' => 'New Name'
]);
```

### changeStatus(int $id, string $statusField)

* ✨ Resursun status field-ini toggle edir
* 📥 `$id`: Resursun ID-si, `$statusField`: Status sütununun adı
* 📤 Yenilənmiş model instance-ı
* ⚡️ Nümunə:

```php
// is_active field-ini true/false edir
$product = $productService->changeStatus($productId);
```

### findById(int $id)

* ✨ ID-yə görə tək resurs tapır
* 📥 `$id`: Resursun ID-si
* 📤 Model instance və ya 404 error
* ⚡️ Nümunə:

```php
$user = $userService->findById($userId);
```

## ⚠️ Vacib Qeydlər

* Repository class-ları `BaseRepositoryInterface`-i implement etməlidir
* Böyük data setləri üçün `paginateAndFilter()` istifadə edin
* Service-də business logic, Repository-də data əməliyyatları olmalıdır
* N+1 query problemi üçün Repository-də eager loading istifadə edin

## 🔗 Əlaqəli Komponentlər

* `BaseRepositoryInterface` - Repository contract
* `BaseRepository` - Base repository implementation
* `Model` - Eloquent model class-ları
* `Controller` - Service istifadə edən controller-lər
