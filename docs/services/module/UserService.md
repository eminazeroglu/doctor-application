# UserService

## 🎯 Əsas Məqsəd

* İstifadəçilərin CRUD əməliyyatlarının idarə edilməsi
* Repository pattern vasitəsilə data əməliyyatlarının abstraktlaşdırılması
* İstifadəçi statuslarının və filtrlərinin idarə edilməsi

## 🚀 Sürətli Başlanğıc

~~~php
class UserController extends Controller 
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function store(UserRequest $request)
    {
        return $this->userService->create($request->validated());
    }
}
~~~

## 📋 Əsas İstifadə Halları

### 1. İstifadəçi İdarəetməsi

~~~php
// Yeni istifadəçi yaratmaq
$user = $userService->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password')
]);

// İstifadəçi məlumatlarını yeniləmək
$userService->update($userId, [
    'name' => 'Updated Name',
    'phone' => '+994501234567'
]);
~~~

* İstifadəçi qeydiyyatı üçün
* İstifadəçi profilinin yenilənməsi üçün

### 2. İstifadəçi Siyahısı və Axtarış

~~~php
// Səhifələnmiş və filtrli siyahı
$users = $userService->paginateAndFilter();

// Aktiv istifadəçiləri əldə etmək
$activeUsers = $userService->findActiveList();

// Konkret istifadəçini tapmaq
$user = $userService->findById($userId);
~~~

* Admin panelində istifadəçi siyahısı üçün
* Aktiv istifadəçiləri filtrlənək üçün

## 🔧 Metodlar və İstifadəsi

### create(array $data)

* ✨ Yeni istifadəçi yaradır
* 📥 `$data`: İstifadəçi məlumatları
* 📤 User modeli
* ⚡️ Nümunə:

~~~php
$user = $userService->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password')
]);
~~~

### update(int $id, array $data)

* ✨ Mövcud istifadəçini yeniləyir
* 📥 `$id`: İstifadəçi ID-si, `$data`: Yeni məlumatlar
* 📤 `boolean`
* ⚡️ Nümunə:

~~~php
$success = $userService->update($userId, [
    'name' => 'New Name',
    'email' => 'new@example.com'
]);
~~~

### changeStatus(int $id, string $statusField)

* ✨ İstifadəçinin status sahəsini dəyişir
* 📥 `$id`: İstifadəçi ID-si, `$statusField`: Status sahəsinin adı
* 📤 User modeli
* ⚡️ Nümunə:

~~~php
$user = $userService->changeStatus($userId, 'is_active');
// və ya
$user = $userService->changeStatus($userId, 'is_blocked');
~~~

### findById(int $id)

* ✨ ID-yə görə istifadəçini tapır
* 📥 `$id`: İstifadəçi ID-si
* 📤 User modeli
* ⚡️ Nümunə:

~~~php
$user = $userService->findById($userId);
~~~

## ⚠️ Vacib Qeydlər

* `create` və `update` metodları validasiya etmir, validasiya Request-də aparılmalıdır
* `paginateAndFilter` metodu varsayılan olaraq 15 element qaytarır
* Status dəyişikliyi toggle şəklində işləyir (true/false)
* `delete` metodu soft delete istifadə edir

## 🔗 Əlaqəli Komponentlər

* `UserRepository` - Database əməliyyatları
* `User Model` - İstifadəçi modeli
* `UserRequest` - Validasiya qaydaları
* `BaseCrudService` - Base service funksionallığı
