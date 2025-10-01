<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\SettingResource;
use App\Services\App\Upload\ImageUploadService;
use App\Services\Module\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends ApiController
{
    public function __construct(SettingService $service)
    {
        parent::__construct($service, 'config');
        $this->setResource(SettingResource::class);
    }

    /**
     * Bütün tənzimləmələri əldə edir.
     * ApiController-in index metodu pagination və filter dəstəkləyir,
     * ancaq bizə bütün dəyərləri birbaşa vermək lazımdır.
     */
    public function index(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $settings = $this->service->all();

            return response()->json([
                'data' => $this->toResource($settings)
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Konkret tənzimləməni göstərir.
     * ApiController-in show metodunu istifadə etmirik çünki
     * biz ID ilə deyil, key ilə axtarış edirik.
     */
    public function show(mixed $id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $setting = $this->service->get($id);

            if (!$setting) {
                return response()->json(['message' => 'Setting not found'], 404);
            }

            return response()->json([
                'data' => $this->toResource([
                    $id => $setting
                ])
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Tənzimləməni yeniləyir.
     * ApiController-in update metodundan istifadə etmirik çünki
     * biz ID ilə deyil, key ilə yeniləmə edirik.
     */
    public function update(Request $request, mixed $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {

            // Əvvəlcə mövcud settingi əldə edirik
            $setting = $this->service->get($id);

            if (!$setting) {
                return response()->json(['message' => 'Setting not found'], 404);
            }

            // Validasiya qaydalarını əldə edirik

            $rules = $this->commonRules();

            // validateRequest metoduna rules-ı ötürürük
            $this->validateRequest($request, $rules);
            $values = $request->input('values');

            // Şəkil sahələrini müəyyən edirik
            $imageFields = $this->getImageFields($id);

            // Hər bir şəkil sahəsi üçün yoxlama edirik
            foreach ($imageFields as $field) {
                if ($request->hasFile($field)) {
                    // ImageUploadService-i yaradırıq
                    $imageService = new ImageUploadService();

                    // Əvvəlki şəkili siləcəyik
                    $oldImage = $values[$field] ?? null;

                    // Şəkili yükləyirik
                    $image = $imageService
                        ->setFile($request->file($field))
                        ->setPath('setting') // Bütün settinglər üçün path 'setting' olacaq
                        ->setName($field . '_' . time()) // Unikal ad yaradırıq
                        ->setRemoveFile($oldImage)
                        ->upload();

                    if ($image) {
                        $values[$field] = $image;
                    }
                }
                else {
                    $values[$field] = $setting[$field] ?? null;
                }
            }

            // Base64 şəkilləri yoxlayırıq
            foreach ($imageFields as $field) {
                if (isset($values[$field]) && is_string($values[$field]) &&
                    Str::startsWith($values[$field], 'data:image')) {

                    $imageService = new ImageUploadService();
                    $oldImage = $setting[$field] ?? null;

                    $image = $imageService
                        ->setFile($values[$field])
                        ->setPath('setting')
                        ->setName($field . '_' . time())
                        ->setBase64(true)
                        ->setRemoveFile($oldImage)
                        ->upload();

                    if ($image) {
                        $values[$field] = $image;
                    }
                    else if ($setting[$field]) {
                        $values[$field] = $setting[$field];
                    }
                }
                else {
                    $values[$field] = $setting[$field] ?? null;
                }
            }

            // Settingi yeniləyirik
            $setting = $this->service->set($id, $values);

            return response()->json([
                'data' => $this->toResource([
                    $id => $setting
                ]),
                'message' => t('messages.updated')
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function commonRules(): array
    {
        $key = request()->route('key');
        $baseRules = ['values' => 'required|array'];

        return array_merge(
            $baseRules,
            $this->getSettingSpecificRules($key) ?? []
        );
    }

    /**
     * Hər bir setting növü üçün şəkil sahələrini qaytarır
     */
    private function getImageFields(string $key): array
    {
        return match($key) {
            'info' => ['logo', 'logo_dark', 'mobile_logo', 'mobile_logo_dark', 'favicon', 'wallpaper', 'watermark', 'join_us_wallpaper', 'app_qr', 'default_image'],
            default => []
        };
    }

    private function getSettingSpecificRules(string $key): array
    {
        return match($key) {
            'info' => [
                'values.email' => 'required|email'
            ],
            'mail' => [
                'values.host' => 'required|string',
                'values.port' => 'required|integer',
                'values.username' => 'required|string',
                'values.password' => 'required|string'
            ],
            // bütün settinglər üçün qaydalar...
            default => []
        };
    }
}
