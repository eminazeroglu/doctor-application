# ApiResourceRegister

## 🎯 Əsas Məqsəd

* Laravel resource route-ları üçün əlavə metodlar təmin edir
* Custom API endpoint-lərini standartlaşdırır
* RESTful API strukturunu genişləndirir

## 🚀 Sürətli Başlanğıc

~~~php
// routes/api.php
Route::apiResource('users', UserController::class);

// Yaradılan route-lar:
GET    /api/users/select
GET    /api/users
POST   /api/users
GET    /api/users/{user}
POST   /api/users/{user}/action
PUT    /api/users/{user}
DELETE /api/users/{user}
DELETE /api/users
~~~

## 📋 Custom Route-lar

### 1. Select Route
~~~php
// GET /api/users/select
protected function addResourceSelect($name, $base, $controller, $options)
{
    $uri = $this->getResourceUri($name . '/select');
    $action = $this->getResourceAction($name, $controller, 'select', $options);
    return $this->router->get($uri, $action);
}

// Controller-də:
public function select()
{
    return $this->service->findActiveList();
}
~~~

### 2. Action Route
~~~php
// POST /api/users/{user}/action
protected function addResourceAction($name, $base, $controller, $options)
{
    $uri = $this->getResourceUri($name . '/{' . str($name)->singular() . '}/action');
    $action = $this->getResourceAction($name, $controller, 'action', $options);
    return $this->router->post($uri, $action);
}

// Controller-də:
public function action(Request $request, $id)
{
    return $this->service->changeStatus($id, $request->action);
}
~~~

### 3. Bulk Delete Route
~~~php
// DELETE /api/users
protected function addResourceDestroyAll($name, $base, $controller, $options)
{
    $uri = $this->getResourceUri($name);
    $action = $this->getResourceAction($name, $controller, 'destroyAll', $options);
    return $this->router->delete($uri, $action);
}

// Controller-də:
public function destroyAll(Request $request)
{
    return $this->service->deleteMultiple($request->ids);
}
~~~

## 🔧 Route Strukturu

### Default Routes
```
Method   URI                          Action
--------------------------------------------------
GET      /api/users/select           select
GET      /api/users                  index
POST     /api/users                  store
GET      /api/users/{user}           show
POST     /api/users/{user}/action    action
PUT      /api/users/{user}           update
DELETE   /api/users/{user}           destroy
DELETE   /api/users                  destroyAll
```

## ⚠️ Vacib Qeydlər

1. Route Pattern:
    * Resource adı çoxluq formasında olmalıdır (users, products)
    * Parameter adı təklik formasında olur (user, product)
    * Action route-da tire (-) altxətt (_) ilə əvəz olunur

2. HTTP Metodları:
    * select: GET
    * action: POST
    * destroyAll: DELETE

3. URI Structure:
    * List: `/api/users`
    * Single: `/api/users/{user}`
    * Action: `/api/users/{user}/action`
    * Select: `/api/users/select`

## 💡 İstifadə Nümunələri

### Basic Resource Route
~~~php
// routes/api.php
Route::apiResource('products', ProductController::class);

// Controller
class ProductController extends ApiController
{
    public function select()
    {
        return ProductResource::collection(
            $this->service->findActiveList()
        );
    }

    public function action(Request $request, $id)
    {
        return $this->service->changeStatus($id, 'is_active');
    }

    public function destroyAll(Request $request)
    {
        return $this->service->deleteMultiple($request->ids);
    }
}
~~~

### Custom Route Options
~~~php
Route::apiResource('products', ProductController::class)
    ->except(['destroyAll'])
    ->names([
        'select' => 'products.dropdown',
        'action' => 'products.status'
    ]);
~~~

## 🔗 API Endpoint İstifadəsi

### Select Endpoint
~~~php
// GET /api/products/select
// Active məhsulların siyahısı üçün
$response = [
    {
        "id": 1,
        "name": "Product 1"
    }
];
~~~

### Action Endpoint
~~~php
// POST /api/products/1/action
{
    "action": "activate"
}

// və ya
{
    "action": "deactivate"
}
~~~

### Bulk Delete
~~~php
// DELETE /api/products
{
    "ids": [1, 2, 3]
}
~~~

## 🛠️ Best Practices

1. Route Naming:
~~~php
Route::apiResource('products', ProductController::class)
    ->names([
        'index' => 'products.list',
        'select' => 'products.dropdown'
    ]);
~~~

2. Route Documentation:
~~~php
/**
 * @OA\Get(
 *     path="/api/products/select",
 *     summary="Get active products for dropdown"
 * )
 */
public function select()
{
    // ...
}
~~~

3. Route Middleware:
~~~php
Route::apiResource('products', ProductController::class)
    ->middleware(['auth:sanctum']);
~~~
