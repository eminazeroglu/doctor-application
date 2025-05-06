<?php

namespace App\Models;

use App\Helpers\Helper;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Translate extends Model
{
    use SoftDeletes;

    protected $fillable = ['key', 'value', 'locale', 'is_system'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }

    public static function getTranslation($key, $locale = null)
    {
        $locale = $locale ?: Helper::language();
        $translation = static::where('key', $key)->where('locale', $locale)->first();
        return $translation ? $translation->value : $key;
    }

    /**
     * @throws Exception
     */
    public static function setTranslation($key, $value, $locale = null)
    {
        try {
            DB::beginTransaction();
            Schema::disableForeignKeyConstraints();
            $locale = $locale ?: Helper::language();
            $data = static::updateOrCreate(
                ['key' => $key, 'locale' => $locale],
                ['value' => $value]
            );
            Schema::enableForeignKeyConstraints();
            DB::commit();

            return $data;
        }
        catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    public function scopeCurrent($query)
    {
        return $query->where('locale', Helper::language());
    }

    public static function getCurrentTranslations()
    {
        return static::where('locale', Helper::language())->get();
    }
}
