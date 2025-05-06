# BlueprintProvider

## 🎯 Əsas Məqsəd

* Laravel migration-ları üçün özəlləşdirilmiş makrolar təqdim edir
* Təkrarlanan sütun strukturlarını asanlaşdırır
* Database sxemini standartlaşdırır

## 🚀 Sürətli Başlanğıc

~~~php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->key();                   // UUID
    $table->string('name');
    $table->photo();                 // photo_path
    $table->photo('thumbnail');      // thumbnail_path
    $table->slug();                  // SEO URL
    $table->translates();            // Çoxdilli
    $table->trackable();             // Audit
    $table->timestamps();
});
~~~

## 📋 Makrolar və İstifadəsi

### 1. photo(string $field = 'photo')
~~~php
// Default istifadə
$table->photo();          // photo_path

// Parametrli istifadə
$table->photo('avatar');  // avatar_path
$table->photo('cover');   // cover_path
$table->photo('banner');  // banner_path

// Nümunə struktur
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->photo();                 // Əsas şəkil
    $table->photo('avatar');         // Profil şəkli
    $table->photo('cover');          // Cover şəkli
});
~~~

### 2. slug()
~~~php
$table->slug();

// Yaradır:
'slug' => string
+ index üçün
~~~

### 3. translates()
~~~php
$table->translates();

// JSON strukturu:
{
    "az": {
        "name": "Məhsul",
        "description": "Təsvir"
    },
    "en": {
        "name": "Product",
        "description": "Description"
    },
    "ru": {
        "name": "Продукт",
        "description": "Описание"
    }
}
~~~

### 4. key()
~~~php
$table->key();

// Yaradır:
uuid (unique, indexed)
~~~

### 5. trackable()
~~~php
$table->trackable();

// Yaradır:
created_by: unsignedBigInteger, nullable
updated_by: unsignedBigInteger, nullable
created_by_name: string, nullable
updated_by_name: string, nullable
+ foreign keys
~~~

## 🔧 Real Dünya Nümunələri

### E-commerce Məhsul
~~~php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->key();
    $table->string('name');
    $table->decimal('price', 10, 2);
    
    // Müxtəlif şəkillər
    $table->photo();                 // Əsas şəkil
    $table->photo('thumbnail');      // Kiçik şəkil
    $table->photo('gallery');        // Qalereya
    
    $table->slug();
    $table->translates();
    $table->trackable();
    $table->timestamps();
});
~~~

### Blog Post
~~~php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->key();
    
    // Şəkillər
    $table->photo('cover');          // Cover şəkil
    $table->photo('thumbnail');      // List üçün thumbnail
    
    $table->slug();
    $table->translates();
    $table->trackable();
    $table->timestamps();
});
~~~

### İstifadəçi Profili
~~~php
Schema::create('user_profiles', function (Blueprint $table) {
    $table->id();
    $table->key();
    
    // Şəkillər
    $table->photo('avatar');         // Profil şəkli
    $table->photo('cover');          // Cover
    
    $table->translates();
    $table->trackable();
    $table->timestamps();
});
~~~

## ⚠️ Vacib Qeydlər

1. Şəkil path-ləri:
    * Həmişə nullable
    * Həmişə `_path` sonluğu
    * Custom adlandırma imkanı

2. Audit tracking:
    * Həm ID, həm ad saxlanılır
    * Cascade yox, nullOnDelete

3. Translates:
    * JSON strukturu dəyişməz
    * Multi-language support

## 💡 Best Practices

1. Şəkil adlandırması:
~~~php
// DÜZGÜN
$table->photo();                 // Ümumi şəkil
$table->photo('thumbnail');      // Thumbnail
$table->photo('cover');          // Cover

// YANLIŞ
$table->photo('image');          // image_path çox ümumi ad olar
$table->photo('photo');          // photo_path təkrar səslənir
~~~

2. Migration strukturu:
~~~php
$table->id();
$table->key();                   // İlk öncə ID-lər
$table->string('name');          // Sonra əsas fieldlər
$table->photo();                 // Şəkillər
$table->slug();                  // SEO
$table->translates();            // Çoxdilli
$table->trackable();            // Audit
$table->timestamps();           // System timestamps
~~~

3. Foreign key əlaqələri:
    * `nullOnDelete()` istifadə edin
    * Soft delete nəzərə alın

4. Index istifadəsi:
    * UUID həmişə indexed
    * Slug həmişə indexed
    * Foreign keys avtomatik indexed
