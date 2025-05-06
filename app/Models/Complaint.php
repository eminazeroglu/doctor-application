<?php

namespace App\Models;

use App\Enums\ComplaintStatusEnum;
use App\Enums\ComplaintTypeEnum;
use App\Traits\Model\HasCode;
use App\Traits\Model\HasLoggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Complaint extends BaseModel
{
    use HasLoggable, HasCode;

    /**
     * Doldurula bilən sahələr.
     * Bu sahələrə kənardan data yaza bilərik.
     */
    protected $fillable = [
        'uuid',
        'code',
        'user_id',
        'complaintable_type',
        'complaintable_id',
        'title',
        'description',
        'attachments',
        'status',
        'resolution_note',
        'resolved_at',
        'resolved_by'
    ];

    /**
     * Avtomatik cast ediləcək sahələr
     */
    protected $casts = [
        'attachments' => 'json',
        'resolved_at' => 'datetime'
    ];

    /**
     * Virtual atributlar - hər dəfə modeli çağıranda əlavə olunacaq
     */
    protected $appends = ['status_text', 'complaintable_type_text'];

    /*
|--------------------------------------------------------------------------
| RELATIONSHIPS - ƏLAQƏLƏR
|--------------------------------------------------------------------------
*/

    /**
     * Şikayəti edən istifadəçi ilə əlaqə
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Şikayəti həll edən moderator/admin ilə əlaqə
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Şikayət edilən obyektlə əlaqə (user, company və ya listing)
     */
    public function complaintable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Bu şikayətə aid olan bütün mesajlar
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ComplaintMessage::class)->orderBy('created_at', 'desc');
    }

    /**
     * ATTRIBUTES - Virtual Atributlar
     * Bunlar real cədvəl sütunları deyil, modelə əlavə edilən hesablanmış dəyərlərdir
     */
    protected function statusText(): Attribute
    {
        return new Attribute(
            get: fn() => ComplaintStatusEnum::getDescription($this->status)
        );
    }

    protected function complaintableTypeText(): Attribute
    {
        return new Attribute(
            get: fn() => ComplaintTypeEnum::getDescription($this->complaintable_type)
        );
    }

    /**
     * SCOPES - Filter Metodları
     * Bu metodlar çağırıldıqları zaman query-ni filterləyir
     * Məsələn: Complaint::pending()->get()
     */
    public function scopePending($query)
    {
        return $query->where('status', ComplaintStatusEnum::Pending);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', ComplaintStatusEnum::InProgress);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', ComplaintStatusEnum::Resolved);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ComplaintStatusEnum::Rejected);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', ComplaintStatusEnum::Closed);
    }

    /**
     * HELPERS - Köməkçi Metodlar
     * Bu metodlar şikayətin vəziyyətini və təsdiqlərini yoxlamaq üçün istifadə olunur
     */

    /**
     * Şikayətə cavab yazıla bilər mi?
     * Bağlı və ya rədd edilmiş şikayətlərə cavab yazıla bilməz
     */
    public function canReply(): bool
    {
        return !in_array($this->status, [
            ComplaintStatusEnum::Closed,
            ComplaintStatusEnum::Rejected
        ]);
    }

    /**
     * Şikayətin hər hansı bir vəziyyətdə olmasını yoxlayır
     */
    public function isResolved(): bool
    {
        return $this->status === ComplaintStatusEnum::Resolved;
    }

    public function isPending(): bool
    {
        return $this->status === ComplaintStatusEnum::Pending;
    }

    public function isInProgress(): bool
    {
        return $this->status === ComplaintStatusEnum::InProgress;
    }

    public function isClosed(): bool
    {
        return $this->status === ComplaintStatusEnum::Closed;
    }

    /**
     * Bildiriş göndəriləcək istifadəçini tapır
     * Şikayət növünə görə müvafiq istifadəçini qaytarır
     */
    public function getNotifiableUser(): ?User
    {
        return match($this->complaintable_type) {
            User::class => $this->complaintable,
            default => null
        };
    }
}
