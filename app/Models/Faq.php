<?php

namespace App\Models;

use App\Traits\Model\HasTranslate;

class Faq extends BaseModel
{
    use HasTranslate;

    protected $fillable = [
        'translates',
        'is_active',
    ];

    // Add any additional methods or relationships here
    public function getTranslatableAttributes(): array
    {
        return [
            'question',
            'answer',
        ];
    }
}
