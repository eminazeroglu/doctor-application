<?php

namespace App\Rules;

use App\Services\Module\SettingService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FileFormatRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $format = app(SettingService::class)->get('upload', 'allowed_file_types.document');
        $fileFormat = $format['file'];
        if (in_array(mime_content_type($value), $fileFormat)) {
            $fail('validator.InvalidFileFormat');
        }
    }
}
