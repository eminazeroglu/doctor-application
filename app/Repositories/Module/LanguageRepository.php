<?php

namespace App\Repositories\Module;

use App\Models\Language;
use App\Repositories\BaseRepository;
use App\Services\Filter\LanguageFilter;

class LanguageRepository extends BaseRepository
{
    public function __construct(Language $model)
    {
        parent::__construct($model);
        $this->setFilter(new LanguageFilter(request()));
    }

    public function getLanguagesWithTranslates($locale)
    {
        return $this->executeWithCache(__FUNCTION__ . $locale, function () use ($locale) {
            $language = $this->baseQuery()
                ->with('translates')
                ->where('locale', $locale)
                ->firstOrFail();
            return $language->translates()->where('is_system', 0)->get()->map(fn($i) => [
                'key' => $i->key,
                'text' => $i->value,
                'locale' => $i->locale,
            ]);
        });
    }
}
