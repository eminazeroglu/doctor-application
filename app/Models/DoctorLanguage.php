<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorLanguage extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'language',
        'level',
        'custom_fields'
    ];

    protected $casts = [
        'custom_fields' => 'json'
    ];

    protected $appends = ['level_text'];

    /**
     * Bu dil biliyinin sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Dil səviyyəsinin insan-oxuna bilən versiyasını qaytarır
     */
    protected function levelText(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->level) {
                    'beginner' => 'Başlanğıc',
                    'intermediate' => 'Orta',
                    'advanced' => 'Yüksək',
                    'native' => 'Ana dili',
                    default => $this->level,
                };
            }
        );
    }
}
