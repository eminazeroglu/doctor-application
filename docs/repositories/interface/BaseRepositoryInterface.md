# BaseRepositoryInterface

## 🎯 Əsas Məqsəd

* Repository pattern üçün base interface təmin edir
* CRUD əməliyyatları üçün standart metodları müəyyən edir
* Bütün repository-lər üçün ümumi kontrakt təyin edir

## 🚀 Sürətli Başlanğıc

~~~php
// Repository class yaratmaq
class UserRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }
    
    // Digər metodların implementasiyası
}
~~~

## 📋 Əsas İstifadə Halları

### 1. Yeni Repository Yaratmaq

~~~php
// ProductRepository.php
class ProductRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }
    
    // Digər metodlar...
}
~~~

* Yeni model üçün repository yaradarkən
* CRUD əməliyyatlarının standardlaşdırılması üçün

### 2. Service Layer İnteqrasiyası

~~~php
class ProductService
{
    protected $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getActiveProducts()
    {
        return $this->repository->findActiveList();
    }
}
~~~

* Service və Repository arasında abstraction təmin etmək üçün
* Dependency injection istifadəsi üçün

## 🔧 Metodlar və İstifadəsi

### create(array $data)

* ✨ Yeni resurs yaradır
* 📥 `$data`: Yaradılacaq məlumatlar
* 📤 Model instance
* ⚡️ Nümunə:

~~~php
public function create(array $data)
{
    return $this->model->create($data);
}
~~~

### update(int $id, array $data)

* ✨ Mövcud resursu yeniləyir
* 📥 `$id`: Resurs ID-si, `$data`: Yeni məlumatlar
* 📤 boolean/Model
* ⚡️ Nümunə:

~~~php
public function update(int $id, array $data)
{
    $model = $this->findById($id);
    return $model->update($data);
}
~~~

### findById(int $id)

* ✨ ID-yə görə resursu tapır
* 📥 `$id`: Resurs ID-si
* 📤 Model instance
* ⚡️ Nümunə:

~~~php
public function findById(int $id)
{
    return $this->model->findOrFail($id);
}
~~~

### paginateAndFilter()

* ✨ Səhifələnmiş və filtrli məlumatları qaytarır
* 📥 Parametrsiz
* 📤 LengthAwarePaginator
* ⚡️ Nümunə:

~~~php
public function paginateAndFilter(): LengthAwarePaginator
{
    return $this->model->paginate(15);
}
~~~

## ⚠️ Vacib Qeydlər

* Bütün metodlar mütləq implement edilməlidir
* Return type-lar concrete repository-də dəqiqləşdirilə bilər
* Interface-də validation logic olmamalıdır
* `findActiveList` aktiv elementləri qaytarmalıdır

## 🔗 Əlaqəli Komponentlər

* Concrete Repository class-ları
* Model class-ları
* Service class-ları
* Business logic layer

## 🛠 İmplementasiya Nümunəsi

~~~php
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $model = $this->findById($id);
        return $model->update($data);
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function changeStatus(int $id, string $statusField = 'is_active')
    {
        $model = $this->findById($id);
        $model->{$statusField} = !$model->{$statusField};
        $model->save();
        return $model;
    }

    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }
}
~~~
