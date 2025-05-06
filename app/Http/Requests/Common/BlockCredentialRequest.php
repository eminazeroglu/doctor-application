<?php

namespace App\Http\Requests\Common;

use App\Enums\CredentialTypeEnum;
use App\Http\Requests\BaseRequest;
use App\Rules\AzerbaijanPhoneRule;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Closure;

class BlockCredentialRequest extends BaseRequest
{
    /**
     * Validasiya qaydaları
     */
    public function rules(): array
    {
        $rules = [
            'type' => [
                'required',
                'in:' . implode(',', CredentialTypeEnum::getValues())
            ],
            'value' => [
                'required',
                'string',
                'max:255',
                // Tip əsasında validasiya funksiyası
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');

                    $isValid = match($type) {
                        CredentialTypeEnum::Email => $this->validateEmail($value),
                        CredentialTypeEnum::Phone => $this->validatePhone($value, $fail),
                        CredentialTypeEnum::IP => $this->validateIp($value),
                        default => false
                    };

                    if (!$isValid && $type !== CredentialTypeEnum::Phone) {
                        $fail($this->getCredentialErrorMessage($type));
                    }
                }
            ],
            'reason' => 'required|string|min:10|max:1000',
            'blocked_until' => [
                'nullable',
                'date',
                $this->validateBlockedUntil()
            ]
        ];

        // Əgər update əməliyyatıdırsa (yəni route-da ID parametri varsa)
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Mövcud yazını unique yoxlamasından istisna edirik
            $rules['value'][] = Rule::unique('blocked_credentials')
                ->where('type', $this->input('type'))
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->ignore($this->route('blocked_credential')); // route-dan ID-ni alırıq
        } else {
            // Create əməliyyatı üçün normal unique qaydası
            $rules['value'][] = Rule::unique('blocked_credentials')
                ->where('type', $this->input('type'))
                ->where('is_active', true)
                ->whereNull('deleted_at');
        }

        return $rules;
    }

    /**
     * Email validasiyası
     */
    protected function validateEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Telefon validasiyası - AzerbaijanPhoneRule istifadə edir
     */
    protected function validatePhone(string $value, Closure $fail): bool
    {
        $phoneRule = new AzerbaijanPhoneRule([
            'mobileOnly' => true, // Yalnız mobil nömrələrə icazə veririk
            'strictFormat' => true // Ciddi format yoxlaması
        ]);

        // Validasiya xətası olsa da, mesajı phone rule özü idarə edəcək
        $phoneRule->validate('value', $value, $fail);

        // Xəta mesajını phone rule özü handle etdiyi üçün true qaytarırıq
        return true;
    }

    /**
     * IP validasiyası
     */
    protected function validateIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Blok müddətinin validasiyası
     */
    protected function validateBlockedUntil(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($value !== null && $value !== 'permanent') {
                $blockedUntil = Carbon::parse($value);

                if ($blockedUntil->isPast()) {
                    $fail('Block tarixi keçmiş tarix ola bilməz.');
                    return;
                }

                if ($blockedUntil->diffInYears(now()) > 5) {
                    $fail('Block müddəti 5 ildən çox ola bilməz.');
                }
            }
        };
    }

    /**
     * Xəta mesajları
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Block tipi məcburidir',
            'type.in' => 'Yanlış block tipi',
            'value.required' => 'Block ediləcək dəyər məcburidir',
            'value.unique' => 'Bu dəyər artıq bloklanıb',
            'reason.required' => 'Block səbəbi məcburidir',
            'reason.min' => 'Block səbəbi minimum :min simvol olmalıdır',
            'blocked_until.date' => 'Yanlış tarix formatı'
        ];
    }

    /**
     * Credential tipinə görə xəta mesajı qaytarır
     */
    protected function getCredentialErrorMessage(string $type): string
    {
        return match ($type) {
            CredentialTypeEnum::Email => 'Düzgün email formatı daxil edin',
            CredentialTypeEnum::IP => 'Düzgün IP ünvanı daxil edin',
            default => 'Yanlış format'
        };
    }

    /**
     * Validasiyadan əvvəl dataları hazırlayırıq
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('blocked_until') === 'permanent') {
            $this->merge(['blocked_until' => null]);
        }
    }
}
