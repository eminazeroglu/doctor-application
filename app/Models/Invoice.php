<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends BaseModel
{
    use SoftDeletes;

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'payment_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'notes',
        'items',
        'billing_details'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'total' => 'float',
        'items' => 'json',
        'billing_details' => 'json',
        'status' => 'string',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['status_text', 'is_paid', 'is_overdue', 'formatted_total'];

    /**
     * Faktura statusunun mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function statusText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return InvoiceStatusEnum::getDescription($this->status);
            }
        );
    }

    /**
     * Fakturanın ödənilmiş olub-olmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isPaid(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->status === InvoiceStatusEnum::Paid;
            }
        );
    }

    /**
     * Fakturanın vaxtının keçib-keçmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isOverdue(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->due_date) {
                    return false;
                }

                return $this->due_date->isPast() && $this->status === InvoiceStatusEnum::Sent;
            }
        );
    }

    /**
     * Faktura məbləğini formatlaşdırılmış şəkildə qaytarır.
     * @return AttributeAlias
     */
    public function formattedTotal(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return number_format($this->total, 2) . ' ' . $this->payment->currency;
            }
        );
    }

    /**
     * Fakturaya aid ödəniş əlaqəsi.
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Fakturanı göndərilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsSent(): bool
    {
        $this->status = InvoiceStatusEnum::Sent;
        return $this->save();
    }

    /**
     * Fakturanı ödənilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsPaid(): bool
    {
        $this->status = InvoiceStatusEnum::Paid;
        return $this->save();
    }

    /**
     * Fakturanı vaxtı keçmiş kimi işarələyir.
     * @return bool
     */
    public function markAsOverdue(): bool
    {
        $this->status = InvoiceStatusEnum::Overdue;
        return $this->save();
    }

    /**
     * Fakturanı ləğv edilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsCancelled(): bool
    {
        $this->status = InvoiceStatusEnum::Cancelled;
        return $this->save();
    }

    /**
     * Qaralama fakturalarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatusEnum::Draft);
    }

    /**
     * Göndərilmiş fakturalarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatusEnum::Sent);
    }

    /**
     * Ödənilmiş fakturalarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatusEnum::Paid);
    }

    /**
     * Vaxtı keçmiş fakturalarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('status', InvoiceStatusEnum::Sent)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now());
        })->orWhere('status', InvoiceStatusEnum::Overdue);
    }

    /**
     * Ləğv edilmiş fakturalarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatusEnum::Cancelled);
    }

    /**
     * Faktura nömrəsinə görə axtarış.
     * @param Builder $query
     * @param string $invoiceNumber
     * @return Builder
     */
    public function scopeWithInvoiceNumber(Builder $query, string $invoiceNumber): Builder
    {
        return $query->where('invoice_number', $invoiceNumber);
    }

    /**
     * Faktura tarixinə görə axtarış.
     * @param Builder $query
     * @param string $date
     * @return Builder
     */
    public function scopeWithInvoiceDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('invoice_date', $date);
    }

    /**
     * Son ödəniş tarixinə görə axtarış.
     * @param Builder $query
     * @param string $date
     * @return Builder
     */
    public function scopeWithDueDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('due_date', $date);
    }

    /**
     * Minimum məbləğə görə axtarış.
     * @param Builder $query
     * @param float $amount
     * @return Builder
     */
    public function scopeMinTotal(Builder $query, float $amount): Builder
    {
        return $query->where('total', '>=', $amount);
    }

    /**
     * Maksimum məbləğə görə axtarış.
     * @param Builder $query
     * @param float $amount
     * @return Builder
     */
    public function scopeMaxTotal(Builder $query, float $amount): Builder
    {
        return $query->where('total', '<=', $amount);
    }

    /**
     * Son dövrün fakturalarını axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
