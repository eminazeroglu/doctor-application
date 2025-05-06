# UserRepository

## 🎯 Əsas Məqsəd

* İstifadəçi modelinə aid database əməliyyatlarının idarə edilməsi
* İstifadəçi spesifik sorğu və filterlərin təmin edilməsi
* İstifadəçilərin təhlükəsiz şəkildə yaradılması və idarə edilməsi

## 🚀 Sürətli Başlanğıc

~~~php
class UserController extends Controller 
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function store(UserRequest $request)
    {
        return $this->repository->create($request->validated());
    }
}
~~~

## 📋 Əsas İstifadə Halları

### 1. İstifadəçi İdarəetməsi

~~~php
// İstifadəçi yaratmaq
$user = $repository->create([
    'name' => 'John',
    'surname' => 'Doe',
    'email' => 'john@example.com',
    'password' => 'secret123'
]);

// Email ilə axtarış
$user = $repository->findByEmail('john@example.com');

// Status dəyişmək
$user = $repository->changeStatus($userId, 'is_block');
~~~

* Qeydiyyat prosesi üçün
* İstifadəçi axtarışı üçün
* Status idarəetməsi üçün

### 2. Filtrlənmiş Axtarış

~~~php
// URL: /api/users?fullname=john doe&email=john&role=2&limit=10
$users = $repository->paginateAndFilter();
~~~

* Admin panel üçün
* İstifadəçi siyahısı və axtarışı üçün

## 🔧 Metodlar və İstifadəsi

### create(array $data)

* ✨ Yeni istifadəçi yaradır
* 📥 `$data`: İstifadəçi məlumatları
* 📤 User modeli
* ⚡️ Nümunə:

~~~php
$user = $repository->create([
    'name' => 'John',
    'surname' => 'Doe',
    'email' => 'john@example.com',
    'password' => 'secret'
]);
~~~

### findByEmail(string $email)

* ✨ Email ilə istifadəçi axtarır
* 📥 `$email`: İstifadəçi email-i
* 📤 User modeli və ya null
* ⚡️ Nümunə:

~~~php
$user = $repository->findByEmail('john@example.com');
~~~

### paginateAndFilter()

* ✨ Filtrlənmiş və səhifələnmiş istifadəçi siyahısı
* 📥 Request parametrləri (query string)
* 📤 LengthAwarePaginator
* ⚡️ Nümunə:

~~~php
// URL parametrləri ilə axtarış
$users = $repository->paginateAndFilter();
// /users?fullname=john&email=@example.com&role=2
~~~

## ⚠️ Vacib Qeydlər

* Support istifadəçisi ('support@app.com') siyahıda görünmür
* Şifrələr avtomatik hash-lənir
* Yeni istifadəçilər default olaraq role_id=1 ilə yaradılır
* Ad və soyad birləşdirilərək tam ad axtarışı mümkündür

## 💡 Filter Parametrləri

| Parameter  | Təsvir | Nümunə |
|------------|---------|----------|
| fullname   | Ad və soyada görə axtarış | ?fullname=john doe |
| email      | Email-ə görə axtarış | ?email=john@ |
| code       | Koda görə axtarış | ?code=ABC123 |
| role       | Rol ID-yə görə filter | ?role=2 |
| limit      | Səhifədəki element sayı | ?limit=20 |
| sort       | Sıralama sahəsi | ?sort=created_at |
| direction  | Sıralama istiqaməti | ?direction=desc |

## 🔍 Axtarış Xüsusiyyətləri

* Tam ad axtarışı həm "ad soyad" həm də "soyad ad" formatında işləyir
* Email və kod axtarışları partial match istifadə edir
* Rol filterləməsi exact match istifadə edir
* Bütün axtarışlar case-insensitive-dir

## 🔗 Əlaqəli Komponentlər

* `User Model` - İstifadəçi modeli
* `Hash` - Şifrə hash-ləmə
* `UserRequest` - Validasiya
* `UserResource` - API response format
