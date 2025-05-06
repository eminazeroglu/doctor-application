# BaseModel

## 🎯 Əsas Məqsəd

* Bütün modellər üçün ortaq funksionallıqları təmin edir
* Avtomatik UUID, slug, şəkil və audit özəlliklərini əlavə edir
* Standart davranışları və formatlamaları mərkəzi şəkildə idarə edir

## 🚀 Sürətli Başlanğıc

~~~php
class Post extends BaseModel
{
    protected $fillable = ['title', 'content'];
    public $uploadBase64 = true; // Base64 şəkil yükləməsi üçün
}

// İstifadəsi
$post = Post::create([
    'title' => 'My Post',
    'content' => 'Content here',
    'photo_path' => $base64Image
]);
~~~

## 📋 Əsas Xüsusiyyətlər

### 1. Avtomatik UUID və Slug

~~~php
$post = Post::create(['title' => 'My First Post']);

echo $post->uuid; // 550e8400-e29b-41d4-a716-446655440000
echo $post->slug; // my-first-post
~~~

### 2. Şəkil İdarəetməsi

~~~php
class Product extends BaseModel
{
    public $uploadBase64 = true;
}

$product->update([
    'photo_path' => $base64Image
]);

echo $product->photo; // https://domain.com/storage/photos/image.jpg
~~~

### 3. Audit Tracking

~~~php
$post = Post::create(['title' => 'New Post']);

echo $post->created_by; // 1
echo $post->created_by_name; // John Doe
echo $post->updated_by_name; // Jane Doe
~~~

## 🔧 Metodlar və Özəlliklər

### Scopes

* ✨ Hazır query scope-lar
* ⚡️ Nümunə:

~~~php
// Aktiv məlumatlar
$activePosts = Post::active()->get();

// İstifadəçiyə aid məlumatlar
$myPosts = Post::owned()->get();
~~~

### Date Formatlaması

* ✨ Tarixləri ISO 8601 formatında qaytarır
* ⚡️ Nümunə:

~~~php
$post->created_at; // 2024-01-01T12:00:00+00:00
$post->updated_at; // 2024-01-01T15:30:00+00:00
~~~

## ⚠️ Vacib Qeydlər

* `$guarded` massivi `id` və `uuid`-ni qoruyur
* SoftDeletes avtomatik əlavə olunur (`deleted_at` varsa)
* Tərcümələr JSON formatında saxlanılır (`translates` sütunu varsa)
* Audit məlumatları (`created_by_name`, `updated_by_name`) avtomatik doldurulur
* `photo()` attributu şəkil URL-ni qaytarır

## 🔗 Əlaqələr və Traits

### Traits
* `HasFactory` - Factory dəstəyi
* `HasUuid` - UUID generasiyası
* `HasSlug` - SEO-friendly URL
* `HasImage` - Şəkil idarəetməsi
* `SoftDeletes` - Yumşaq silmə

### Relationships
* `creator()` - Yaradan istifadəçi
* `updater()` - Yeniləyən istifadəçi

## 💡 İstifadə Nümunəsi

~~~php
class Article extends BaseModel
{
    protected $fillable = [
        'title',
        'content',
        'photo_path',
        'is_active'
    ];

    public $uploadBase64 = true;

    // Əlavə metodlar
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}

// İstifadəsi
$article = Article::create([
    'title' => 'New Article',
    'content' => 'Content here',
    'photo_path' => $base64Image
]);

// Avtomatik əlavə olunan özəlliklər
echo $article->uuid;            // UUID
echo $article->slug;            // new-article
echo $article->photo;           // Şəkil URL
echo $article->created_by_name; // Yaradan istifadəçinin adı
~~~

## 📝 Database Migration Nümunəsi

~~~php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->key();           // UUID
    $table->string('title');
    $table->text('content');
    $table->photo();         // photo_path
    $table->slug();         // SEO URL
    $table->translates();   // Tərcümələr
    $table->trackable();    // Audit fields
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
~~~
