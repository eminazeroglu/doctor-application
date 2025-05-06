# TranslationService

## 🎯 Əsas Məqsəd

* Dinamik tərcümə sisteminin idarə edilməsi
* Çoxdilli tətbiqlərdə tərcümələrin cache və database ilə işləməsi
* Dil seçimlərinin və tərcümə import/export əməliyyatlarının idarəsi

## 🚀 Sürətli Başlanğıc

~~~php
// Controller və ya service-də istifadə
class ProductController extends Controller 
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function show()
    {
        $title = $this->translationService->getTranslation('product.title');
        $description = $this->translationService->getTranslation('product.description');
    }
}
~~~

## 📋 Əsas İstifadə Halları

### 1. Tərcümələrin İdarəsi

~~~php
// Tərcümə əldə etmək
$title = $translationService->getTranslation('product.title', 'az');

// Tərcümə əlavə etmək
$translationService->setTranslation('product.title', 'Məhsul Başlığı', 'az');

// Cache-i təmizləmək
$translationService->clearTranslationCache('product.title', 'az');
~~~

* Mövcud tərcümələri əldə etmək üçün
* Yeni tərcümələr əlavə etmək üçün
* Cache-in idarəsi üçün

### 2. Dil Konfiqurasiyası

~~~php
// Aktiv dilləri əldə etmək
$activeLanguages = $translationService->getActiveLanguages();

// Default dili təyin etmək
$translationService->setDefaultLanguage('az');

// Cari dili əldə etmək
$currentLang = $translationService->getCurrentLanguage();
~~~

* Mövcud dillərin siyahısını almaq üçün
* Sistemin default dilini dəyişmək üçün
* Cari dil seçimini idarə etmək üçün

## 🔧 Metodlar və İstifadəsi

### getTranslation(string $key, ?string $locale, ?string $default)

* ✨ Tərcümə açarına görə tərcüməni qaytarır
* 📥 `$key`: Tərcümə açarı, `$locale`: Dil kodu, `$default`: Default dəyər
* 📤 Tərcümə edilmiş mətn
* ⚡️ Nümunə:

~~~php
$text = $translationService->getTranslation('welcome.message', 'az', 'Xoş Gəlmisiniz');
~~~

### setTranslation(string $key, string $value, ?string $locale)

* ✨ Yeni tərcümə əlavə edir və ya mövcud olanı yeniləyir
* 📥 `$key`: Tərcümə açarı, `$value`: Tərcümə mətni, `$locale`: Dil kodu
* 📤 Translate modeli
* ⚡️ Nümunə:

~~~php
$translationService->setTranslation('buttons.save', 'Yadda Saxla', 'az');
~~~

### importTranslations(array $translations, ?string $locale)

* ✨ Toplu şəkildə tərcümələri idxal edir
* 📥 `$translations`: Tərcümələr array-i, `$locale`: Dil kodu
* 📤 `void`
* ⚡️ Nümunə:

~~~php
$translationService->importTranslations([
    'welcome.title' => 'Xoş Gəlmisiniz',
    'welcome.subtitle' => 'Saytımıza xoş gəlmisiniz'
], 'az');
~~~

### exportTranslations(?string $locale)

* ✨ Müəyyən dilin bütün tərcümələrini ixrac edir
* 📥 `$locale`: Dil kodu
* 📤 Tərcümələr array-i
* ⚡️ Nümunə:

~~~php
$translations = $translationService->exportTranslations('az');
~~~

## ⚠️ Vacib Qeydlər

* Tərcümələr 1 gün müddətində cache-də saxlanılır
* Cache açarları `translation:{locale}:{key}` formatında yaradılır
* Tərcümə tapılmadıqda, açar və ya default dəyər qaytarılır
* `clearAllTranslationCache()` bütün cache-i təmizləyir, diqqətli istifadə edin

## 🔗 Əlaqəli Komponentlər

* `Language Model` - Dil modelləri
* `Translate Model` - Tərcümə modelləri
* `Helper` - Dil funksiyaları üçün helper class
* `Cache` - Laravel cache sistemi
