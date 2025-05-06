<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\AdvertisementDisplayTypeEnum;
use App\Enums\AdvertisementPositionEnum;
use App\Http\Controllers\ApiController;
use App\Rules\Base64ImageControlRule;
use App\Rules\ImageBase64Rule;
use App\Services\Module\AdvertisementService;

class AdvertisementController extends ApiController
{
    public function __construct(AdvertisementService $service)
    {
        parent::__construct($service, 'advertisement');
    }

    // Add any additional methods here

    public function commonRules(): array
    {
        return [
            'position' => ['required', 'in:' . implode(',', AdvertisementPositionEnum::getValues())],
            'translates' => ['required', 'array'],
            'translates.*.link' => ['nullable', 'url'],
            'translates.*.photo' => ['required', new ImageBase64Rule, new Base64ImageControlRule],
            'expiry_date' => ['required', 'date'],
            'display_type' => ['required', 'in:' . implode(',', AdvertisementDisplayTypeEnum::getValues())],
            'selected_categories' => ['nullable', 'array'],
        ];
    }

    protected function commonMessages(): array
    {
        return [
            'translates.*.link.url' => trans('validation.url'),
        ];
    }
}
