<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CategoryAttributeResource;
use App\Http\Resources\Admin\LocationResource;
use App\Http\Resources\Admin\ReferenceResource;
use App\Services\Module\ReferenceDataService;
use Illuminate\Http\JsonResponse;

class ReferenceDataController extends Controller
{
    public ReferenceDataService $service;

    public function __construct(ReferenceDataService $service)
    {
        $this->service = $service;
    }

    public function permissions(): JsonResponse
    {
        return response()->json($this->service->fetchPermissions());
    }

    /**
     * Gender
     * */
    public function genders(): JsonResponse
    {
        return response()->json($this->service->fetchGenders());
    }

    /**
     * Languages
     * */
    public function languages(): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchLanguages()));
    }

    /**
     * Language With Translates
     * */
    public function languageWithTranslates($locale): JsonResponse
    {
        return response()->json($this->service->fetchLanguagesWithTranslates($locale));
    }

    /**
     * System Image Watermark Position
    */
    public function systemImageWatermarkPosition(): JsonResponse
    {
        return response()->json($this->service->fetchImageWatermarkPosition());
    }

    /**
     * Ana kateqoriyaların siyahısını qaytarır.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->service->fetchCategories();
        return response()->json(ReferenceResource::collection($categories));
    }

    /**
     * Seçilən kateqoriyalara görə alt kateqoriyaların siyahısını qaytarır.
     */
    public function categoryChildren($parentId): JsonResponse
    {
        $categories = $this->service->fetchCategoriesByParentId($parentId);
        return response()->json(ReferenceResource::collection($categories));
    }

    /**
     * Seçilən kateqoriyalara görə atributların siyahısını qaytarır.
     */
    public function categoryAttributes($id): JsonResponse
    {
        $categories = $this->service->fetchCategoryAttributes($id);
        return response()->json(CategoryAttributeResource::collection($categories));
    }

    /**
     * Attributeların listəsi
    */
    public function attributes(): JsonResponse
    {
        return response()->json($this->service->fetchAttributes());
    }

    /**
     * Attributelara ait bütün tipləri gətirir
     * */
    public function attributeTypes(): JsonResponse
    {
        return response()->json($this->service->fetchAttributeTypes());
    }

    /**
     * Atributun yerləşmə mövqelərini qaytarır.
     * */
    public function attributePositions(): JsonResponse
    {
        return response()->json($this->service->fetchAttributePositions());
    }

    /**
     * Seçilmiş atributun aktiv optionlarını qaytarır.
     * Select, MultiSelect tipli atributlar üçün seçim variantlarını təmin edir
     */
    public function attributeOptions(int $id): JsonResponse
    {
        $options = $this->service->fetchAttributeOptions($id);
        return response()->json(ReferenceResource::collection($options));
    }

    /**
     * Parent optiona bağlı olan child optionları qaytarır.
     * Bu method ümumi istifadə üçündür və istənilən dependent option strukturunda işləyir.
     * Məsələn:
     * - Marka-Model
     * - Ölkə-Şəhər
     * - Kateqoriya-Altakateqoriya və s.
     */
    public function dependentOptions(int $parentOptionId): JsonResponse
    {
        $options = $this->service->fetchDependentOptions($parentOptionId);
        return response()->json(ReferenceResource::collection($options));
    }

    /**
     * Country
     */
    public function countries(): JsonResponse
    {
        return response()->json(LocationResource::collection($this->service->fetchCountries()));
    }

    /**
     * Country With Cities
     */
    public function countryWithCities($uuid): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchCountryWithCities($uuid)));
    }

    /**
     * Cities
     */
    public function cities(): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchCities()));
    }

    /**
     * City With Regions
     */
    public function cityWithRegions($uuid): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchCityWithRegions($uuid)));
    }

    /**
     * City With Subways
     */
    public function cityWithSubways($uuid): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchCityWithSubways($uuid)));
    }

    /**
     * Regions
     */
    public function regions(): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchRegions()));
    }

    /**
     * Region With Subways
     */
    public function regionWithSubways($uuid): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchRegionWithSubways($uuid)));
    }

    /**
     * Subways
     */
    public function subways(): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchSubways()));
    }

    /**
     * Currencies
     * */
    public function currencies(): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchCurrencies()));
    }

    /**
     * Clinics
     * */
    public function clinics(): JsonResponse
    {
        return response()->json(ReferenceResource::collection($this->service->fetchClinics()));
    }


}
