# AuthService

## 🎯 Əsas Məqsəd

* Autentifikasiya və Avtorizasiya əməliyyatları üçün xidmət təmin edir
* İstifadəçi qeydiyyatı, giriş, şifrə sıfırlama və email təsdiqləmə kimi əməliyyatları idarə edir
* Təhlükəsizlik və istifadəçi sessiyası ilə bağlı business logic-i ehtiva edir

## 🚀 Sürətli Başlanğıc

~~~php
class AuthController extends Controller 
{
    protected AuthService $authService;

    public function __construct(AuthService $authService) 
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->login($request->validated());
    }
}
~~~

## 📋 Əsas İstifadə Halları

### 1. İstifadəçi Qeydiyyatı və Giriş

~~~php
// Qeydiyyat
$authService->register([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secure_password'
]);

// Giriş
$response = $authService->login([
    'email' => 'john@example.com',
    'password' => 'secure_password'
]);
// Returns: ['token' => '...', 'user' => UserResource]
~~~

* Yeni istifadəçi qeydiyyatı və xoş gəldiniz emaili göndərmək üçün
* Giriş və token yaratmaq üçün

### 2. Şifrə Sıfırlama

~~~php
// Şifrə sıfırlama linki göndər
$authService->sendResetLinkEmail(['email' => 'user@example.com']);

// Şifrəni yenilə
$authService->resetPassword([
    'token' => 'reset_token',
    'password' => 'new_password',
    'password_confirmation' => 'new_password'
]);
~~~

* İstifadəçi şifrəsini unutduqda
* Şifrə yeniləmə prosesi üçün

## 🔧 Metodlar və İstifadəsi

### register(array $data)

* ✨ Yeni istifadəçi qeydiyyatdan keçirir
* 📥 `$data`: İstifadəçi məlumatları (name, email, password)
* 📤 `void`
* ⚡️ Nümunə:

~~~php
$authService->register([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123'
]);
~~~

### login(array $credentials)

* ✨ İstifadəçi giriş sessiyası yaradır
* 📥 `$credentials`: Email və şifrə
* 📤 Token və istifadəçi məlumatları
* ⚡️ Nümunə:

~~~php
$response = $authService->login([
    'email' => 'john@example.com',
    'password' => 'password123'
]);
~~~

### sendResetLinkEmail(array $data)

* ✨ Şifrə sıfırlama linki göndərir
* 📥 `$data`: İstifadəçi emaili
* 📤 `void`
* ⚡️ Nümunə:

~~~php
$authService->sendResetLinkEmail(['email' => 'user@example.com']);
~~~

### resetPassword(array $data)

* ✨ Şifrəni yeniləyir
* 📥 `$data`: Token və yeni şifrə
* 📤 Yeni token və istifadəçi məlumatları
* ⚡️ Nümunə:

~~~php
$response = $authService->resetPassword([
    'token' => 'reset_token',
    'password' => 'new_password'
]);
~~~

## ⚠️ Vacib Qeydlər

* Şifrə sıfırlama tokenləri 60 dəqiqə müddətində etibarlıdır
* Email təsdiqləməsi məcburidir - təsdiqlənməmiş emaillar ilə giriş mümkün deyil
* Bloklanmış istifadəçilər sistemə daxil ola bilməzlər
* Token yenilənməsi zamanı köhnə tokenlər silinir
* CSRF və XSS hücumlarından qorunmaq üçün token-based autentifikasiya istifadə olunur

## 🔗 Əlaqəli Komponentlər

* `UserRepository` - İstifadəçi data əməliyyatları
* `AuthResource` - İstifadəçi response formatı
* `WelcomeEmailMail` - Xoş gəldiniz email şablonu
* `PasswordResetMail` - Şifrə sıfırlama email şablonu
* `User Model` - İstifadəçi modeli
