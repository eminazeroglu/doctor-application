<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class AppointmentFilter extends BaseFilter
{
    /**
     * Filterlənə bilən sahələr
     */
    protected array $filters = [
        'search',
        'status',
        'doctor_id',
        'patient_id',
        'clinic_id',
        'service_id',
        'consultation_type',
        'is_paid',
        'date_range',
        'start_date',
        'end_date',
        'is_active',
        'trashed'
    ];

    /**
     * Axtarış sahələri
     */
    protected function getSearchableFields(): array
    {
        return ['complaint', 'notes'];
    }

    /**
     * Axtarış filtri - xəstə və həkim adları da daxil olmaqla
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function (Builder $q) use ($value) {
            // Randevu məlumatlarında axtarış
            $q->where('complaint', 'like', "%{$value}%")
                ->orWhere('notes', 'like', "%{$value}%");
        });
    }

    /**
     * Status filtri
     */
    protected function filterStatus(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('appointment_status', $value);
        }

        return $query->where('appointment_status', $value);
    }

    /**
     * Həkim filtri
     */
    protected function filterDoctorId(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('doctor_id', $value);
        }

        return $query->where('doctor_id', $value);
    }

    /**
     * Xəstə filtri
     */
    protected function filterPatientId(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('patient_id', $value);
        }

        return $query->where('patient_id', $value);
    }

    /**
     * Klinika filtri
     */
    protected function filterClinicId(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('clinic_id', $value);
        }

        return $query->where('clinic_id', $value);
    }

    /**
     * Xidmət filtri
     */
    protected function filterServiceId(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('service_id', $value);
        }

        return $query->where('service_id', $value);
    }

    /**
     * Konsultasiya növü filtri
     */
    protected function filterConsultationType(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('consultation_type', $value);
        }

        return $query->where('consultation_type', $value);
    }

    /**
     * Ödəniş statusu filtri
     */
    protected function filterIsPaid(Builder $query, $value): Builder
    {
        return $query->where('is_paid', $value);
    }

    /**
     * Başlama tarixi filtri
     */
    protected function filterStartDate(Builder $query, $value): Builder
    {
        return $query->whereDate('start_time', '>=', $value);
    }

    /**
     * Bitmə tarixi filtri
     */
    protected function filterEndDate(Builder $query, $value): Builder
    {
        return $query->whereDate('start_time', '<=', $value);
    }

    /**
     * Bu gün randevular
     */
    protected function filterToday(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereDate('start_time', today());
        }

        return $query;
    }

    /**
     * Bu həftə randevular
     */
    protected function filterThisWeek(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereBetween('start_time', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }

        return $query;
    }

    /**
     * Bu ay randevular
     */
    protected function filterThisMonth(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereBetween('start_time', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ]);
        }

        return $query;
    }

    /**
     * Gələcək randevular
     */
    protected function filterUpcoming(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->where('start_time', '>', now())
                ->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled']);
        }

        return $query;
    }

    /**
     * Keçmiş randevular
     */
    protected function filterPast(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->where('end_time', '<', now());
        }

        return $query;
    }

    /**
     * Aktiv randevular
     */
    protected function filterActive(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled']);
        }

        return $query;
    }
}
