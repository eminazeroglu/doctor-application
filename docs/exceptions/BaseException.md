# BaseException

## 🎯 Əsas Məqsəd

* API xətalarını standartlaşdırmaq
* Xəta mesajlarını formatlamaq
* HTTP status kodlarını idarə etmək

## 🚀 Sürətli Başlanğıc

~~~php
// Controller və ya service-də istifadə
if (!Auth::check()) {
    throw new BaseException('Unauthorized access', 401);
}

// Validasiya xətası
throw new BaseException(
    ['email' => 'Email already exists'],
    422
);
~~~

## 📋 Xüsusiyyətlər

### 1. Standart Xəta

~~~php
throw new BaseException('Something went wrong');

// Response:
{
    "error": "BaseException",
    "message": "Something went wrong"
}
// Status: 400
~~~

### 2. Validasiya Xətası

~~~php
throw new BaseException([
    'email' => 'Invalid email format',
    'name' => 'Name is required'
], 422);

// Response:
{
    "email": "Invalid email format",
    "name": "Name is required"
}
// Status: 422
~~~

## 🔧 İstifadə Halları

### Exception Fırlatma

~~~php
class UserService 
{
    public function update($id, $data)
    {
        $user = User::find($id);
        
        if (!$user) {
            throw new BaseException('User not found', 404);
        }
        
        if ($user->is_blocked) {
            throw new BaseException('User is blocked', 403);
        }
        
        // Update əməliyyatı
    }
}
~~~

### Validasiya Xətaları

~~~php
class AuthService
{
    public function login($credentials)
    {
        if (!Auth::attempt($credentials)) {
            throw new BaseException([
                'email' => 'Invalid credentials'
            ], 422);
        }
    }
    
    public function register($data)
    {
        if (User::where('email', $data['email'])->exists()) {
            throw new BaseException([
                'email' => 'Email already registered'
            ], 422);
        }
    }
}
~~~

## ⚠️ Status Kodları

| Kod | İstifadə |
|-----|----------|
| 400 | Default (Bad Request) |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

## 💡 İstifadə Nümunələri

### Business Logic Xətaları

~~~php
class OrderService
{
    public function create($data)
    {
        // Stock yoxlaması
        if (!$this->checkStock($data['product_id'])) {
            throw new BaseException('Product out of stock', 400);
        }
        
        // Ödəniş limiti
        if ($data['amount'] > $this->getLimit()) {
            throw new BaseException('Amount exceeds limit', 400);
        }
        
        // Validasiya
        if (!$this->validateAddress($data['address'])) {
            throw new BaseException([
                'address' => 'Invalid delivery address'
            ], 422);
        }
    }
}
~~~

### Auth Xətaları

~~~php
class AuthController
{
    public function login(Request $request)
    {
        try {
            // Login əməliyyatı
        } catch (BaseException $e) {
            return response()->json([
                'error' => 'AuthError',
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }
}
~~~

### Response Nümunələri

~~~php
// Standart xəta (400)
{
    "error": "BaseException",
    "message": "Invalid operation"
}

// Validasiya xətası (422)
{
    "email": "Email format is invalid",
    "password": "Password is too short"
}

// Not found (404)
{
    "error": "BaseException",
    "message": "Resource not found"
}
~~~

## 🔗 Best Practices

### 1. Xəta Kodları
~~~php
// Sabit kodlardan istifadə
const ERROR_CODES = [
    'NOT_FOUND' => 404,
    'UNAUTHORIZED' => 401,
    'VALIDATION' => 422
];

throw new BaseException('Not found', self::ERROR_CODES['NOT_FOUND']);
~~~

### 2. Mesaj Formatı
~~~php
// Validasiya
throw new BaseException([
    'field' => 'Error message'
], 422);

// Digər
throw new BaseException(
    'Human readable error message',
    400
);
~~~

### 3. Try-Catch İstifadəsi
~~~php
try {
    // Əməliyyat
} catch (BaseException $e) {
    // Log
    logger()->error($e->getMessage());
    
    // Response
    return response()->json([
        'error' => class_basename($e),
        'message' => $e->getMessage()
    ], $e->getCode());
}
~~~
