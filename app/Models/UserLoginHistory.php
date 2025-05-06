<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginHistory extends BaseModel
{

    protected $table = 'user_login_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'location',
        'device_type',
        'meta_data',
        'logged_in_at',
        'logged_out_at',
    ];

    protected $casts = [
        'meta_data' => 'json',
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /*
     * Aid olduğu istifadəçi
     * */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
