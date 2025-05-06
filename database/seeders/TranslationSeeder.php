<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\Translate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\Module\TranslationService;

class TranslationSeeder extends Seeder
{
    protected $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Əsas seed metodu
     * @throws \Exception
     */
    public function run()
    {
        // Verilənlər bazasında transaksiya başladırıq
        DB::beginTransaction();

        try {
            // Dilləri əlavə edirik
            $this->seedLanguages();
            // Tərcümələri əlavə edirik
            $this->seedTranslations();
            // Keşi təmizləyirik
            $this->translationService->clearAllTranslationCache();

            // Əgər hər şey uğurludursa, dəyişiklikləri yadda saxlayırıq
            DB::commit();
        } catch (\Exception $e) {
            // Xəta baş verərsə, dəyişiklikləri geri alırıq
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sistemdə mövcud olan dilləri əlavə edir
     */
    protected function seedLanguages(): void
    {
        $configuredLanguages = [
            ['name' => 'Azərbaycan', 'locale' => 'az', 'is_default' => true],
            ['name' => 'English', 'locale' => 'en'],
            ['name' => 'Русский', 'locale' => 'ru'],
//            ['name' => 'Türkçe', 'locale' => 'tr'],
//            ['name' => 'Arapça', 'locale' => 'ar'],
//            ['name' => 'Fransızca', 'locale' => 'fr'],
//            ['name' => 'Japonca', 'locale' => 'ja'],
//            ['name' => 'Macarca', 'locale' => 'ma'],
        ];

        // Bazada olan bütün dilləri əldə edirik
        $existingLanguages = Language::all();

        // Config-də olan locale-ları array-ə yığırıq
        $configuredLocales = array_column($configuredLanguages, 'locale');

        // Bazada olan amma config-də olmayan dilləri silirik və tərcümələrini təmizləyirik
        foreach ($existingLanguages as $existingLanguage) {
            if (!in_array($existingLanguage->locale, $configuredLocales)) {
                // Əvvəlcə tərcümələri silirik
                $this->deleteAllTranslationsForLocale($existingLanguage->locale);

                // Sonra dili silirik
                $existingLanguage->forceDelete();
            }
        }

        // Config-də olan dilləri yeniləyirik və ya əlavə edirik
        foreach ($configuredLanguages as $language) {
            Language::updateOrCreate(
                ['locale' => $language['locale']],
                [
                    'name' => $language['name'],
                    'is_active' => true,
                    'is_default' => $language['is_default'] ?? false
                ]
            );
        }

        // Default dil yoxdursa, ilk aktiv dili default edirik
        if (!Language::where('is_default', true)->exists()) {
            $firstActiveLanguage = Language::where('is_active', true)->first();
            if ($firstActiveLanguage) {
                $firstActiveLanguage->update(['is_default' => true]);
            }
        }
    }

    /**
     * Bütün dillər üçün tərcümələri əlavə edir
     */
    protected function seedTranslations(): void
    {
        // Bütün dillərin locale-larını əldə edirik
        $locales = Language::pluck('locale')->toArray();

        foreach ($locales as $locale) {
            if ($this->localeFileExists($locale)) {
                // JSON fayldan tərcümələri oxuyuruq
                $translations = $this->getTranslationsForLocale($locale);
                // Tərcümələri sinxronizasiya edirik
                $this->syncTranslationsForLocale($locale, $translations);
            } else {
                // Əgər fayl mövcud deyilsə, bu dil üçün olan bütün tərcümələri silirik
                $this->deleteAllTranslationsForLocale($locale);
                Log::warning("Translation file not found for locale: {$locale}. All translations for this language have been deleted.");
            }
        }
    }

    /**
     * Verilmiş dil üçün tərcümə faylının mövcudluğunu yoxlayır
     */
    protected function localeFileExists(string $locale): bool
    {
        $path = public_path("lang/translations/{$locale}.json");
        return File::exists($path);
    }

    /**
     * JSON fayldan tərcümələri oxuyur və düzləşdirilmiş formata çevirir
     */
    protected function getTranslationsForLocale(string $locale): array
    {
        $path = public_path("lang/translations/{$locale}.json");
        $translations = json_decode(File::get($path), true);
        return $this->flattenTranslations($translations);
    }

    /**
     * Çox səviyyəli JSON strukturunu düz struktura çevirir
     */
    protected function flattenTranslations(array $translations, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($translations as $key => $value) {
            // Əgər bu enum açarıdırsa, onda bütün alt açarlar enum. ilə başlamalıdır
            $currentPrefix = $prefix ? $prefix : ($key === 'enums' ? 'enums' : '');

            if (is_array($value)) {
                if ($key === 'enums') {
                    // Enum-lar üçün xüsusi emal
                    $nestedFlattened = $this->flattenTranslations($value, $currentPrefix);
                } else {
                    // Digər açarlar üçün normal emal
                    $nestedPrefix = $currentPrefix
                        ? "{$currentPrefix}.{$key}"
                        : $key;
                    $nestedFlattened = $this->flattenTranslations($value, $nestedPrefix);
                }
                $flattened = array_merge($flattened, $nestedFlattened);
            } else {
                // Array deyilsə birbaşa açar-dəyər cütü yaradırıq
                $flatKey = $currentPrefix
                    ? "{$currentPrefix}.{$key}"
                    : $key;
                $flattened[$flatKey] = $value;
            }
        }

        return $flattened;
    }

    /**
     * Verilmiş dil üçün tərcümələri sinxronizasiya edir
     */
    protected function syncTranslationsForLocale(string $locale, array $translationsArray): void
    {
        // Bazadan mövcud tərcümələri əldə edirik
        $dbTranslations = Translate::where('locale', $locale)->get();

        // Mövcud tərcümələri yoxlayırıq
        foreach ($dbTranslations as $dbTranslation) {
            if (!array_key_exists($dbTranslation->key, $translationsArray)) {
                // JSON-da olmayan tərcümələri silirik
                $dbTranslation->forceDelete();
                $this->translationService->clearTranslationCache($dbTranslation->key, $locale);
            } else {
                // Dəyişmiş tərcümələri yeniləyirik
                if ($dbTranslation->value !== $translationsArray[$dbTranslation->key]) {
                    $dbTranslation->value = $translationsArray[$dbTranslation->key];
                    $dbTranslation->save();
                    $this->translationService->clearTranslationCache($dbTranslation->key, $locale);
                }
                unset($translationsArray[$dbTranslation->key]);
            }
        }

        // Yeni tərcümələri əlavə edirik
        foreach ($translationsArray as $key => $value) {
            Translate::create([
                'key' => $key,
                'value' => $value,
                'locale' => $locale,
                'is_system' => true
            ]);
            $this->translationService->clearTranslationCache($key, $locale);
        }
    }

    /**
     * Verilmiş dil üçün bütün tərcümələri silir
     */
    protected function deleteAllTranslationsForLocale(string $locale): void
    {
        $translations = Translate::where('locale', $locale)->get();
        foreach ($translations as $translation) {
            $this->translationService->clearTranslationCache($translation->key, $locale);
        }
        Translate::where('locale', $locale)->forceDelete();
    }
}
