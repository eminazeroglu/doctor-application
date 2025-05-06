<?php

namespace App\Models;

use App\Models\Concerns\User\HasPermissions;
use App\Models\Concerns\User\HasRelationships;
use App\Models\Concerns\User\HasAttributes;
use App\Traits\Model\HasCode;
use App\Traits\Model\HasImage;
use App\Traits\Model\HasLoggable;
use App\Traits\Model\HasNotification;
use App\Traits\Model\HasSlug;
use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory,
        HasNotification,
        HasApiTokens,
        HasUuid,
        HasSlug,
        HasCode,
        HasImage,
        HasLoggable;

    // User modelinə aid concerns
    use HasRelationships,
        HasAttributes,
        HasPermissions;

    protected $fillable = [
        'email',
        'password',
        'uuid',
        'role_id',
        'birthday',
        'phone',
        'provider_id',
        'provider',
        'language',
        'name',
        'surname',
        'username',
        'gender',
        'address',
        'is_system',
        'photo_path',
        'status',
        'email_verified_at',
        'referral_code',
        'referral_balance',
        'main_balance'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'social_links' => 'json',
            'referral_balance' => 'float',
            'main_balance' => 'float'
        ];
    }

    protected $appends = ['fullname', 'photo', 'gender_text', 'status_text'];

    /*
    |--------------------------------------------------------------------------
    | CONFIGURATION METHODS - Konfiqurasiya metodları
    |--------------------------------------------------------------------------
    */
    protected function getSlugSourceColumn(): string
    {
        return 'fullname';
    }

    protected function getSlugFieldName(): string
    {
        return 'username';
    }

    public function getImageFields(): array
    {
        return [
            'photo_path' => [
                'path' => 'user',
                'base64' => true,
            ]
        ];
    }
}
