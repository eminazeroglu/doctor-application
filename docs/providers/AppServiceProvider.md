# AppServiceProvider

## 🎯 Əsas Məqsəd

* Laravel application-un əsas konfiqurasiyalarını təmin edir
* Repository patternin və API resurslarının bind-nı idarə edir
* Schema və HTTPS konfiqurasiyalarını təşkil edir

## 🚀 Sürətli Başlanğıc

~~~php
// config/app.php
'providers' => [
    // ...
    App\Providers\AppServiceProvider::class,
];
~~~

## 📋 Əsas Konfiqurasiyalar

### 1. Repository Pattern Bind

~~~php
// Repository Interface və Implementation bind
$this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);

// İstifadəsi
class UserService 
{
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}
~~~

* Dependency Injection üçün
* Loosely coupled architecture üçün

### 2. API Resource Routing

~~~php
// routes/api.php
Route::apiResource('users', UserController::class);

// ApiResourceRegister ilə xüsusi route-lar əlavə olunur
class ApiResourceRegister extends ResourceRegistrar 
{
    // Əlavə route methodları
}
~~~

* RESTful API route-ları üçün
* Custom API route-larının əlavə edilməsi üçün

## 🔧 Konfiqurasiya Detalları

### Schema Configuration

* ✨ MySQL utf8mb4 support üçün string uzunluğunu tənzimləyir
* ⚡️ Nümunə:

~~~php
Schema::defaultStringLength(191);

// Migration nümunəsi
Schema::create('users', function (Blueprint $table) {
    $table->string('name'); // max 191 character
});
~~~

### HTTPS Force

* ✨ Production mühitində HTTPS məcburi edir
* ⚡️ Nümunə:

~~~php
if (app()->environment() !== 'local'):
    $url->forceScheme('https');
endif;
~~~

## ⚠️ Vacib Qeydlər

* Local development mühitində HTTPS məcburi deyil
* Repository pattern bütün layihə boyu istifadə olunur
* String length limiti MySQL utf8mb4 encoding üçün vacibdir
* API resursları xüsusi route registrar istifadə edir

## 🔗 Əlaqəli Komponentlər

* `BaseRepository` - Base repository implementation
* `BaseRepositoryInterface` - Repository kontraktı
* `ApiResourceRegister` - Custom API route qeydiyyatı
* `ResourceRegistrar` - Laravel resource registrar

## 💡 Environment Specific Davranış

### Local Environment
* HTTPS məcburi deyil
* Debug mode aktiv
* Development tools enable

### Production Environment
* HTTPS məcburidir
* Təhlükəsizlik tədbirləri aktiv
* Optimization enable

## 🛠 Provider Strukturu

~~~php
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Container bindings
        // Service registrations
    }

    public function boot(UrlGenerator $url): void
    {
        // Configuration settings
        // URL configurations
        // Pattern bindings
    }
}
~~~

## 📝 Custom Route Registration Nümunəsi

~~~php
// ApiResourceRegister class
class ApiResourceRegister extends ResourceRegistrar
{
    protected $resourceDefaults = ['index', 'store', 'show', 'update', 'destroy', 'status'];
    
    protected function addResourceStatus($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/status';
        $action = $this->getResourceAction($name, $controller, 'status', $options);
        
        return $this->router->patch($uri, $action);
    }
}
~~~
