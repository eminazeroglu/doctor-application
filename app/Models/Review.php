<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory, HasUuid;

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'patient_id',
        'doctor_id',
        'clinic_id',
        'appointment_id',
        'comment',
        'rating',
        'is_anonymous',
        'is_verified',
        'is_moderated',
        'is_active'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
        'is_verified' => 'boolean',
        'is_moderated' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['rating_stars', 'helpful_count', 'status', 'author_name'];

    /**
     * Qiymətləndirməni ulduz şəklində qaytarır.
     * @return string
     */
    public function getRatingStarsAttribute(): string
    {
        $stars = str_repeat('★', $this->rating);
        $emptyStars = str_repeat('☆', 5 - $this->rating);
        return $stars . $emptyStars;
    }

    /**
     * Faydalı hesab edilən sayı qaytarır.
     * @return int
     */
    public function getHelpfulCountAttribute(): int
    {
        return $this->helpful()->where('is_helpful', true)->count();
    }

    /**
     * Rəyin statusunu qaytarır.
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Deaktiv';
        }

        if (!$this->is_moderated) {
            return 'Moderasiya gözləyir';
        }

        if (!$this->is_verified) {
            return 'Təsdiqlənməyib';
        }

        return 'Aktiv';
    }

    /**
     * Rəy müəllifinin adını qaytarır.
     * @return string
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonim';
        }

        return $this->patient->full_name;
    }

    /**
     * Rəyə aid xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Rəyə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Rəyə aid klinika əlaqəsi.
     * @return BelongsTo
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Rəyə aid randevu əlaqəsi.
     * @return BelongsTo
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Rəyə aid cavablar əlaqəsi.
     * @return HasMany
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ReviewResponse::class);
    }

    /**
     * Rəyə aid şikayətlər əlaqəsi.
     * @return HasMany
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }

    /**
     * Rəyə aid faydalılıq qeydləri əlaqəsi.
     * @return HasMany
     */
    public function helpful(): HasMany
    {
        return $this->hasMany(ReviewHelpful::class);
    }

    /**
     * Rəyə aid kriteriya qiymətləndirmələri əlaqəsi.
     * @return HasMany
     */
    public function criteriaRatings(): HasMany
    {
        return $this->hasMany(ReviewRating::class);
    }

    /**
     * Rəyin kriteriyaları əlaqəsi.
     * @return BelongsToMany
     */
    public function criteria(): BelongsToMany
    {
        return $this->belongsToMany(ReviewCriteria::class, 'review_ratings', 'review_id', 'criteria_id')
            ->withPivot(['rating'])
            ->withTimestamps();
    }

    /**
     * Rəy təsdiqlənmiş kimi işarələnir.
     * @return bool
     */
    public function verify(): bool
    {
        $this->is_verified = true;
        return $this->save();
    }

    /**
     * Rəy moderasiyadan keçmiş kimi işarələnir.
     * @return bool
     */
    public function moderate(): bool
    {
        $this->is_moderated = true;
        return $this->save();
    }

    /**
     * Rəy deaktiv edilir.
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Rəy aktiv edilir.
     * @return bool
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Həkim haqqında olan rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeForDoctors(Builder $query): Builder
    {
        return $query->whereNotNull('doctor_id');
    }

    /**
     * Klinika haqqında olan rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeForClinics(Builder $query): Builder
    {
        return $query->whereNotNull('clinic_id');
    }

    /**
     * Konkret həkim haqqında olan rəyləri axtarış.
     * @param Builder $query
     * @param int $doctorId
     * @return Builder
     */
    public function scopeForDoctor(Builder $query, int $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Konkret klinika haqqında olan rəyləri axtarış.
     * @param Builder $query
     * @param int $clinicId
     * @return Builder
     */
    public function scopeForClinic(Builder $query, int $clinicId): Builder
    {
        return $query->where('clinic_id', $clinicId);
    }

    /**
     * Qiymətləndirməyə görə rəyləri axtarış.
     * @param Builder $query
     * @param int $rating
     * @return Builder
     */
    public function scopeWithRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Qiymətləndirməyə görə rəyləri axtarış (minimum).
     * @param Builder $query
     * @param int $minRating
     * @return Builder
     */
    public function scopeMinRating(Builder $query, int $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Aktiv rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Təsdiqlənmiş rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Moderasiyadan keçmiş rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeModerated(Builder $query): Builder
    {
        return $query->where('is_moderated', true);
    }

    /**
     * Anonim rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeAnonymous(Builder $query): Builder
    {
        return $query->where('is_anonymous', true);
    }

    /**
     * Ən yeni rəyləri axtarış.
     * @param Builder $query
     * @param int $limit
     * @return Builder
     */
    public function scopeLatest(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Ən faydalı rəyləri axtarış.
     * @param Builder $query
     * @param int $limit
     * @return Builder
     */
    public function scopeMostHelpful(Builder $query, int $limit = 10): Builder
    {
        return $query->withCount(['helpful' => function($q) {
            $q->where('is_helpful', true);
        }])
            ->orderBy('helpful_count', 'desc')
            ->limit($limit);
    }

    /**
     * Moderasiya gözləyən rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeAwaitingModeration(Builder $query): Builder
    {
        return $query->where('is_moderated', false);
    }

    /**
     * Şikayət edilmiş rəyləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeReported(Builder $query): Builder
    {
        return $query->whereHas('reports', function($q) {
            $q->where('is_resolved', false);
        });
    }
}
