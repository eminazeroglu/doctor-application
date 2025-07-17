<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestResult extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'medical_test_id',
        'result_date',
        'results',
        'summary',
        'interpretation',
        'is_abnormal',
        'file_path',
        'custom_fields'
    ];

    protected $casts = [
        'result_date' => 'date',
        'results' => 'json',
        'is_abnormal' => 'boolean',
        'custom_fields' => 'json'
    ];

    /**
     * Bu nəticənin aid olduğu test
     */
    public function medicalTest(): BelongsTo
    {
        return $this->belongsTo(MedicalTest::class);
    }

    /**
     * Nəticəni qeyd edən həkim/işçi
     */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
