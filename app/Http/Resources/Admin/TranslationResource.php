<?php

namespace App\Http\Resources\Admin;

use App\Services\Module\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = [
            'id' => $this->id,
            'key' => $this->key,
            'is_system' => $this->is_system,
        ];
        foreach (app(TranslationService::class)->getAllLanguages() as $language):
            $result['translation'][$language->locale] = t($this->key, $language->locale, null, true);
        endforeach;
        return $result;
    }
}
