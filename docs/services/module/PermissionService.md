# PermissionService

## 🎯 Əsas Məqsəd

* Layihədə icazələrin və rolların mərkəzləşdirilmiş şəkildə idarə edilməsi
* Config faylından oxunan icazə və rolların bazaya sinxronizasiyası
* İstifadəçi səlahiyyətlərinin yoxlanması

## 🚀 Sürətli Başlanğıc

~~~php
// config/permissions.php
return [
    'permissions' => [
        'users' => [
            'user.view',
            'user.create',
            'user.edit',
            'user.delete'
        ]
    ],
    'roles' => [
        'admin' => [
            'users' => [
                'user.view',
                'user.create',
                'user.edit',
                'user.delete'
            ]
        ]
    ]
];

// İcazələri sinxronizasiya etmək
$permissionService->syncPermissions();
$permissionService->syncRoles();
~~~

## 📋 Əsas İstifadə Halları

### 1. İcazə və Rolların Sinxronizasiyası

~~~php
// Məsələn, DatabaseSeeder-də
public function run(PermissionService $permissionService)
{
    // İcazələri config-dən bazaya köçürür
    $permissionService->syncPermissions();
    
    // Rolları və onların icazələrini sinxronizasiya edir
    $permissionService->syncRoles();
}
~~~

* Layihəni ilk dəfə qurarkən
* İcazə və rol strukturunu yenilədikdə

### 2. İcazələrin Yoxlanması

~~~php
// Controller və ya middleware-də
if ($permissionService->hasPermission($user, 'user.create')) {
    // İstifadəçinin icazəsi var
}

// Bütün icazələri əldə etmək
$allPermissions = $permissionService->getAllPermissions();

// Müəyyən rolun icazələrini əldə etmək
$adminPermissions = $permissionService->getRolePermissions('admin');
~~~

* İstifadəçi səlahiyyətlərini yoxlamaq üçün
* Dinamik menyu və interfeys elementləri üçün

## 🔧 Metodlar və İstifadəsi

### syncPermissions()

* ✨ Config faylından icazələri bazaya sinxronizasiya edir
* 📥 Parametr yoxdur
* 📤 `void`
* ⚡️ Nümunə:

~~~php
$permissionService->syncPermissions();
~~~

### syncRoles()

* ✨ Rolları və onların icazələrini sinxronizasiya edir
* 📥 Parametr yoxdur
* 📤 `void`
* ⚡️ Nümunə:

~~~php
$permissionService->syncRoles();
~~~

### getAllPermissions()

* ✨ Bütün mövcud icazələri config faylından qaytarır
* 📥 Parametr yoxdur
* 📤 İcazələr array-i
* ⚡️ Nümunə:

~~~php
$permissions = $permissionService->getAllPermissions();
// ['users' => ['user.view', 'user.create', ...]]
~~~

### getRolePermissions(string $roleName)

* ✨ Müəyyən rolun icazələrini qaytarır
* 📥 `$roleName`: Rolun adı
* 📤 İcazələr array-i və ya boş array
* ⚡️ Nümunə:

~~~php
$adminPermissions = $permissionService->getRolePermissions('admin');
~~~

### hasPermission(User $user, string $permission)

* ✨ İstifadəçinin müəyyən icazəyə sahib olub-olmadığını yoxlayır
* 📥 `$user`: İstifadəçi modeli, `$permission`: İcazə adı
* 📤 `boolean`
* ⚡️ Nümunə:

~~~php
if ($permissionService->hasPermission($user, 'user.create')) {
    // İcazə varsa...
}
~~~

## ⚠️ Vacib Qeydlər

* Config faylı mütləq `config/permissions.php` yolunda olmalıdır
* `syncPermissions()` və `syncRoles()` database-ə yazma əməliyyatı etdiyindən tez-tez çağırılmamalıdır
* Rol və icazələr arasında sinxronizasiya avtomatik aparılır
* İcazələr silinmir, yalnız əlavə olunur - təhlükəsizlik üçün manual silinməlidir

## 🔗 Əlaqəli Komponentlər

* `Permission Model` - İcazələrin database modeli
* `Role Model` - Rolların database modeli
* `User Model` - İstifadəçi modeli (role ilə əlaqəli)
* `permissions.php` - İcazə və rol konfiqurasiyaları
