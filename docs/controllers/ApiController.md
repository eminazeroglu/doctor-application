# ApiController

## 🎯 Əsas Məqsəd

* Standart CRUD əməliyyatları üçün base API controller
* FormRequest və ya custom validasiya dəstəyi
* İcazə yoxlaması və resource transformasiyası
* Event-lərin idarə edilməsi

## 🚀 Sürətli Başlanğıc

~~~php
class UserController extends ApiController
{
    public function __construct(UserService $service)
    {
        parent::__construct(
            service: $service,
            permission: 'user',
            formRequestClass: UserRequest::class
        );
        
        $this->setResource(UserResource::class);
    }
}
~~~

## 📋 Əsas Funksionallıq

### 1. CRUD Əməliyyatları

~~~php
// index - GET /api/users
public function index(): JsonResponse

// show - GET /api/users/{id}
public function show($id): JsonResponse

// store - POST /api/users
public function store(Request $request): JsonResponse

// update - PUT /api/users/{id}
public function update(Request $request, $id): JsonResponse

// destroy - DELETE /api/users/{id}
public function destroy($id): JsonResponse

// status change - PATCH /api/users/{id}/action
public function action(Request $request, $id): JsonResponse
~~~

### 2. Validasiya Sistemi

~~~php
// FormRequest ilə
class UserController extends ApiController
{
    public function __construct(UserService $service)
    {
        parent::__construct($service, 'user', UserRequest::class);
    }
}

// və ya Controller-də qaydalarla
protected function storeRules(): array
{
    return [
        'name' => 'required|string',
        'email' => 'required|email|unique:users'
    ];
}

protected function storeMessages(): array
{
    return [
        'name.required' => 'Ad mütləq daxil edilməlidir'
    ];
}
~~~

## 🔧 Xüsusiyyətlər və Metodlar

### Protected Properties

~~~php
protected $service;                    // Service class instance
protected ?string $permission;         // İcazə prefixi
protected array $events = [];          // Event mappings
protected string $forbiddenMessage;    // 403 mesajı
protected ?string $formRequestClass;   // FormRequest class
protected $resourceClass;              // Resource class
~~~

### Resource Transformasiyası

~~~php
protected function setResource(string $resourceClass): void
{
    if (!is_subclass_of($resourceClass, JsonResource::class)) {
        throw new \InvalidArgumentException("Invalid resource class");
    }
    $this->resourceClass = $resourceClass;
}

protected function toResource($data)
{
    return $data instanceof Collection 
        ? $this->resourceClass::collection($data)
        : new $this->resourceClass($data);
}
~~~

### Event Handling

~~~php
class UserController extends ApiController
{
    protected array $events = [
        'store' => UserCreated::class,
        'update' => UserUpdated::class,
        'destroy' => UserDeleted::class
    ];
}
~~~

## ⚠️ Vacib Qeydlər

1. İcazə yoxlaması:
```php
protected function authorizeAction(string $ability): bool
{
    return request()->user()->hasPermission(
        $this->permission . '_' . $ability
    );
}
```

2. Validasiya error formatı:
```php
[
    "email" => "Email artıq istifadə olunub",
    "name" => "Ad mütləq daxil edilməlidir"
]
```

3. Response formatları:
```php
// Success
{
    "data": {...},
    "total": 100  // pagination üçün
}

// Error
{
    "message": "You are not authorized..."
}
```

## 💡 İstifadə Nümunələri

### Basic Controller
~~~php
class ProductController extends ApiController
{
    public function __construct(ProductService $service)
    {
        parent::__construct($service, 'product');
        $this->setResource(ProductResource::class);
    }
}
~~~

### Validasiya ilə
~~~php
class UserController extends ApiController
{
    protected function storeRules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email'
        ];
    }
    
    protected function updateRules(): array
    {
        return [
            'name' => 'string',
            'email' => 'email'
        ];
    }
}
~~~

### Event-lərlə
~~~php
class OrderController extends ApiController
{
    protected array $events = [
        'store' => OrderCreated::class,
        'update' => OrderUpdated::class
    ];
}
~~~

## 🔗 Əlaqəli Komponentlər

* Service class-lar
* FormRequest class-lar
* Resource class-lar
* Event class-lar
* Permission sistemi
