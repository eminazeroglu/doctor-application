# ProfileService

## 🎯 Əsas Məqsəd

* İstifadəçi profil məlumatlarının idarə edilməsi
* Email və şifrə yeniləmə əməliyyatları
* İstifadəçi seçimlərinin (preferences) idarə edilməsi

## 🚀 Sürətli Başlanğıc

~~~php
class ProfileController extends Controller 
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function update(ProfileRequest $request)
    {
        return $this->profileService->updateProfile(auth()->user(), $request->validated());
    }
}
~~~

## 📋 Əsas İstifadə Halları

### 1. Profil Məlumatlarının Yenilənməsi

~~~php
// ProfileController
public function update(ProfileRequest $request)
{
    $updatedUser = $profileService->updateProfile(auth()->user(), [
        'name' => 'John Doe',
        'phone' => '+994501234567',
        'avatar' => $base64Image // base64 formatında şəkil
    ]);
}
~~~

* İstifadəçi profil məlumatlarını yeniləmək üçün
* Avatar şəklini base64 formatında yükləmək üçün

### 2. Email və Şifrə Yeniləmə

~~~php
// Email yeniləmə
$profileService->updateEmail($user, ['email' => 'new@example.com']);

// Şifrə yeniləmə
$profileService->updatePassword($user, [
    'password' => 'new_secure_password'
]);
~~~

* Email dəyişikliyi və təkrar təsdiqləmə
* Təhlükəsiz şifrə yeniləmə

## 🔧 Metodlar və İstifadəsi

### updateProfile(User $user, array $data)

* ✨ İstifadəçi profil məlumatlarını yeniləyir
* 📥 `$user`: Cari istifadəçi, `$data`: Yeni məlumatlar
* 📤 Yenilənmiş User modeli
* ⚡️ Nümunə:

~~~php
$updatedUser = $profileService->updateProfile($user, [
    'name' => 'John Doe',
    'phone' => '+994501234567'
]);
~~~

### updatePassword(User $user, array $data)

* ✨ İstifadəçi şifrəsini yeniləyir
* 📥 `$user`: Cari istifadəçi, `$data`: Yeni şifrə
* 📤 `void`
* ⚡️ Nümunə:

~~~php
$profileService->updatePassword($user, [
    'password' => 'new_secure_password'
]);
~~~

### updateEmail(User $user, array $data)

* ✨ Email ünvanını yeniləyir və təsdiq emaili göndərir
* 📥 `$user`: Cari istifadəçi, `$data`: Yeni email
* 📤 `void`
* ⚡️ Nümunə:

~~~php
$profileService->updateEmail($user, [
    'email' => 'new@example.com'
]);
~~~

### getPreferences(User $user)

* ✨ İstifadəçi seçimlərini qaytarır
* 📥 `$user`: Cari istifadəçi
* 📤 UserPreference modeli
* ⚡️ Nümunə:

~~~php
$preferences = $profileService->getPreferences($user);
~~~

### updatePreferences(User $user, array $data)

* ✨ İstifadəçi seçimlərini yeniləyir
* 📥 `$user`: Cari istifadəçi, `$data`: Yeni seçimlər
* 📤 Yenilənmiş UserPreference modeli
* ⚡️ Nümunə:

~~~php
$preferences = $profileService->updatePreferences($user, [
    'theme' => 'dark',
    'notifications_enabled' => true
]);
~~~

## ⚠️ Vacib Qeydlər

* Email yeniləndikdə hesab deaktiv olur və təkrar təsdiq tələb olunur
* Avatar base64 formatında qəbul edilir (`setUseBase64(true)`)
* Preferences ilk dəfə yaradılırsa, avtomatik user_id əlavə olunur
* React/Frontend URL-i "Origin" header-dən götürülür

## 🔗 Əlaqəli Komponentlər

* `User Model` - İstifadəçi modeli
* `UserPreference Model` - İstifadəçi seçimləri modeli
* `WelcomeEmailMail` - Email təsdiq məktubu
* `ProfileRequest` - Profil yeniləmə validasiyası
