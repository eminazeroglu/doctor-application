<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ImageBase64Rule implements ValidationRule
{
    /**
     * Base64 formatında şəkil olduğunu yoxlayır
     * Düzgün format: data:image/[format];base64,[data]
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Dəyərin string olduğunu və base64 şəkil pattern-ə uyğun olduğunu yoxlayırıq
        if (!is_string($value) || !preg_match('/^data:image\/[\w.-]+;base64,/', $value)) {
            $fail('Şəkil base64 formatında olmalıdır.');
        }
    }
}
