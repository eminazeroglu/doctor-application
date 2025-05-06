<?php

namespace App\Traits\Controller;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait HasValidatesRequests
{
    public ?string $formRequestClass = null;

    /**
     * Form request classını təyin edir
     *
     * @param string|null $formRequestClass
     * @return $this
     */
    public function setFormRequestClass(?string $formRequestClass): self
    {
        $this->formRequestClass = $formRequestClass;
        return $this;
    }

    /**
     * Aktiv form request classını qaytarır
     *
     * @return string|null
     */
    public function getFormRequestClass(): ?string
    {
        return $this->formRequestClass;
    }

    /**
     * Requestin validasiyasını həyata keçirir
     * FormRequest və ya manual qaydalar ilə
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @return array
     * @throws ValidationException
     */
    public function validateRequest(Request $request, array $rules = [], array $messages = []): array
    {
        if ($this->formRequestClass) {
            // FormRequest varsa, onu istifadə edirik
            return app($this->formRequestClass)->validated();
        }

        // Əks halda, Validator ilə qaydalarla işləyirik
        $validator = Validator::make($request->all(), $rules, $messages);

        // Əgər validasiya uğursuz olarsa, `failedValidation()` metodunu çağırırıq
        if ($validator->fails()) {
            $this->failedValidation($validator);
        }

        return $validator->validated();
    }

    /**
     * Validasiya uğursuz olduqda çağrılan metod
     *
     * @param ValidatorContract $validator
     * @throws HttpResponseException
     */
    public function failedValidation(ValidatorContract $validator)
    {
        throw new HttpResponseException(
            response()->json($this->transformValidationErrors($validator->errors()->toArray()), 422)
        );
    }

    /**
     * Validasiya xətalarını transformasiya edir
     *
     * @param array $errors
     * @return array
     */
    public function transformValidationErrors(array $errors): array
    {
        return array_map(fn($error) => $error[0], $errors);
    }

    /**
     * Ortaq validasiya qaydaları
     * Ümumiyyətlə store və update əməliyyatları üçün ortaq olan qaydalar
     *
     * @return array
     */
    public function commonRules(): array
    {
        return [];
    }

    /**
     * Ortaq validasiya mesajları
     *
     * @return array
     */
    public function commonMessages(): array
    {
        return [];
    }

    /**
     * Store əməliyyatı üçün validasiya qaydaları
     *
     * @return array
     */
    public function storeRules(): array
    {
        return $this->commonRules();
    }

    /**
     * Store əməliyyatı üçün validasiya mesajları
     *
     * @return array
     */
    public function storeMessages(): array
    {
        return $this->commonMessages();
    }

    /**
     * Update əməliyyatı üçün validasiya qaydaları
     *
     * @return array
     */
    public function updateRules(): array
    {
        return $this->commonRules();
    }

    /**
     * Update əməliyyatı üçün validasiya mesajları
     *
     * @return array
     */
    public function updateMessages(): array
    {
        return $this->commonMessages();
    }
}
