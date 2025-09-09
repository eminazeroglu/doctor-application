<?php

namespace App\Services\Module;

use App\Enums\GenderEnum;
use App\Enums\ImageWatermarkPositionEnum;
use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Http\Resources\Admin\BaseResource;
use App\Models\Language;
use App\Models\Role;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Testimonial;
use App\Models\User;
use App\Repositories\Module\CategoryRepository;
use App\Repositories\Module\CityRepository;
use App\Repositories\Module\ClinicRepository;
use App\Repositories\Module\CountryRepository;
use App\Repositories\Module\LanguageRepository;
use App\Repositories\Module\RegionRepository;
use App\Repositories\Module\SubwayRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReferenceDataService
{
    protected CategoryRepository $categoryRepository;
    protected CountryRepository $countryRepository;
    protected CityRepository $cityRepository;
    protected RegionRepository $regionRepository;
    protected SubwayRepository $subwayRepository;
    protected ClinicRepository $clinicRepository;

    public function __construct(
        CategoryRepository $categoryRepository,
        CountryRepository  $countryRepository,
        CityRepository     $cityRepository,
        RegionRepository   $regionRepository,
        SubwayRepository   $subwayRepository,
        ClinicRepository   $clinicRepository,
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->countryRepository = $countryRepository;
        $this->cityRepository = $cityRepository;
        $this->regionRepository = $regionRepository;
        $this->subwayRepository = $subwayRepository;
        $this->clinicRepository = $clinicRepository;
    }

    /*
     * Permissions
     * */
    public function fetchPermissions(): AnonymousResourceCollection
    {
        return BaseResource::collection(Role::query()->get());
    }

    /*
     * Genders
     * */
    public function fetchGenders(): \Illuminate\Support\Collection
    {
        return collect(GenderEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => GenderEnum::getDescription($i)
        ]);
    }

    /**
     * Doctor Or Service
     * */
    public function fetchDoctorOrService($search): array
    {
        $doctors = User::query()
            ->where('user_type', UserTypeEnum::Doctor)
            ->where('status', UserStatusEnum::Active)
            ->fullName($search)
            ->limit(5)
            ->get()
            ->map(function ($user) {

                $result = [
                    'username' => $user->username,
                    'fullname' => $user->full_name,
                    'photo' => $user->photo
                ];

                if ($user->doctor->mainWorkplace()?->pivot) {
                    $result['profession'] = $user->doctor->mainWorkplace()->pivot->profession;
                }

                return $result;
            });

        $services = Service::query()
            ->translationSearchInLanguage($search)
            ->limit(5)
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'category_id' => $service->category_id,
                ];
            });

        return [
            'services' => $services,
            'doctors' => $doctors
        ];
    }

    /**
     * Home Page
     * */
    public function fetchHomePage(): array
    {
        $sliders = Slider::query()
            ->active()
            ->get()
            ->map(function ($slider) {
                return [
                    'title' => $slider->title,
                    'description' => $slider->description,
                    'button_text' => $slider->button_text,
                    'button_link' => $slider->button_link,
                    'photo' => $slider->photo,
                ];
            });

        $homeStatistic = setting('homeStatistic');

        $info = setting('info');

        $testimonials = Testimonial::query()
            ->active()
            ->get()
            ->map(function ($testimonial) {
                return [
                    'fullname' => $testimonial->fullname,
                    'profession' => $testimonial->profession,
                    'comment' => $testimonial->comment,
                    'rating' => $testimonial->rating,
                    'photo' => $testimonial->photo,
                ];
            });

        return [
            'app_link' => [
                'google' => $info['google_app_link'],
                'apple' => $info['apple_app_link'],
            ],
            'join_us_wallpaper' => $info['join_us_wallpaper_path'],
            'testimonials' => $testimonials,
            'homeStatistic' => [
                'clinic_count' => $homeStatistic['clinic_count'],
                'doctor_count' => $homeStatistic['doctor_count'],
                'patient_count' => $homeStatistic['patient_count'],
                'practicing_doctor_count' => $homeStatistic['practicing_doctor_count']
            ],
            'sliders' => $sliders
        ];
    }

    /*
     * Languages
     * */
    public function fetchLanguages()
    {
        return Language::query()->active()->get();
    }

    /*
     * Languages With Translates
     * */
    public function fetchLanguagesWithTranslates($locale)
    {
        return app(LanguageRepository::class)->getLanguagesWithTranslates($locale);
    }

    /**
     *
     */
    public function fetchImageWatermarkPosition(): \Illuminate\Support\Collection
    {
        return collect(ImageWatermarkPositionEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => ImageWatermarkPositionEnum::getDescription($i)
        ]);
    }

    /**
     * Active kateqoriyaları gətirir
     */
    public function fetchCategories(): Collection
    {
        return $this->categoryRepository->fetchCategoryByParent();
    }

    /**
     * Kateqoriyaya aid atributları və onların optionlarını qaytarır
     */
    public function fetchCategoryAttributes(int $id)
    {
        return $this->categoryRepository->getCategoryWithAttributes($id);
    }

    public function fetchCountries(): Collection
    {
        return $this->countryRepository->findActiveList();
    }

    public function fetchCountryWithCities($uuid): Collection
    {
        return $this->countryRepository->fetchByUuidWithCities($uuid);
    }

    public function fetchCities(): Collection
    {
        return $this->cityRepository->findActiveList();
    }

    public function fetchCityWithRegions($uuid): Collection
    {
        return $this->cityRepository->fetchByUuidWithRegions($uuid);
    }

    public function fetchCityWithSubways($uuid): Collection
    {
        return $this->cityRepository->fetchByUuidWithSubways($uuid);
    }

    public function fetchRegions(): Collection
    {
        return $this->regionRepository->findActiveList();
    }

    public function fetchRegionWithSubways($uuid): Collection
    {
        return $this->regionRepository->fetchByUuidWithSubways($uuid);
    }

    public function fetchSubways(): Collection
    {
        return $this->subwayRepository->findActiveList();
    }

    /**
     * Attributeların listəsi
     */
    public function fetchAttributes()
    {
        return app(AttributeService::class)->findActiveList();
    }

    /**
     * Xidmətlərin listəsi
     */
    public function fetchServices()
    {
        return app(ServiceService::class)->findActiveList();
    }

    /**
     * Attributlara aid bütün tipləri listələyir
     * */
    public function fetchAttributeTypes()
    {
        return app(AttributeService::class)->getAttributeTypes();
    }

    /**
     * Attributlara aid bütün yerləri listələyir
     * */
    public function fetchAttributePositions()
    {
        return app(AttributeService::class)->getAttributePositions();
    }

    /**
     * Attributlara aid bütün yerləri listələyir
     * */
    public function fetchClinics(): Collection
    {
        return $this->clinicRepository->findActiveList();
    }

}
