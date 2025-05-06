<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBlock extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
        'meta_data'
    ];

    protected $casts = [
        'meta_data' => 'json'
    ];

    /**
     * Blok edən istifadəçini əldə edir
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    /**
     * Blok edilən istifadəçini əldə edir
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
