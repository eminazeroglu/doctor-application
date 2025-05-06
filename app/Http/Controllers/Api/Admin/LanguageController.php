<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Translation\TranslateRequest;
use App\Models\Language;
use App\Models\Translate;
use App\Services\Module\LanguageService;
use Illuminate\Http\JsonResponse;

class LanguageController extends ApiController
{
    public function __construct(LanguageService $service)
    {
        parent::__construct($service, 'language');
    }

    public function commonRules(): array
    {
        return [
            'name' => ['required', 'string'],
            'locale' => ['required', 'string'],
        ];
    }

    public function translations($locale): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $language = Language::query()->where('locale', $locale)->firstOrFail();
            $translations = Translate::query()->where('locale', $language->locale)->paginate(request()->limit);
            return response()->json([
                'data' => $this->toResource($translations),
                'total' => $translations->total()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function updateTranslation(TranslateRequest $request, $locale): JsonResponse
    {
        $language = Language::query()->where('locale', $locale)->firstOrFail();
        $validated = $request->validated();
        $translation = $this->service->setTranslation($validated['key'], $validated['value'], $language->locale);
        return response()->json($this->toResource($translation));
    }

    public function currentLanguage(): JsonResponse
    {
        $language = Language::where('locale', currentLang())->first();
        return response()->json($this->toResource($language));
    }

}
