<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'locale', 'is_active', 'is_default'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function translates(): HasMany
    {
        return $this->hasMany(Translate::class, 'locale', 'locale');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function getDefault()
    {
        return static::default()->first();
    }

    public static function setDefault($locale)
    {
        static::where('is_default', true)->update(['is_default' => false]);
        return static::where('locale', $locale)->update(['is_default' => true]);
    }

    public static function getCurrentLanguage()
    {
        return static::where('locale', Helper::language())->first();
    }

    public function scopeCurrent($query)
    {
        return $query->where('locale', Helper::language());
    }
}
