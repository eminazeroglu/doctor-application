<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentStep extends Model
{
    protected $fillable = [
        'treatment_plan_id',
        'title',
        'description',
        'order',
        'status',
        'due_date',
        'completed_date'
    ];

    protected $casts = [
        'order' => 'integer',
        'due_date' => 'date',
        'completed_date' => 'date'
    ];

    /**
     * Bu addımın aid olduğu müalicə planı
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    /**
     * Addım statusunu yeniləyir
     */
    public function updateStatus(string $status): bool
    {
        $data = ['status' => $status];

        if ($status === 'completed') {
            $data['completed_date'] = now();
        }

        return $this->update($data);
    }
}
