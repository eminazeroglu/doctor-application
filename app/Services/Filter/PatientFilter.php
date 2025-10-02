<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PatientFilter extends BaseFilter
{
    /**
     * Available filters for patients
     */
    protected array $filters = [
        'search',
        'blood_type',
        'gender',
        'age_range',
        'insurance_provider',
        'has_insurance',
        'insurance_expired',
        'has_allergies',
        'has_chronic_diseases',
        'has_active_medications',
        'has_upcoming_appointments',
        'emergency_contact',
        'height_range',
        'weight_range',
        'bmi_category',
        'is_active',
        'created_date_range',
        'last_appointment_date'
    ];

    /**
     * Searchable fields for patients
     */
    protected function getSearchableFields(): array
    {
        return ['medical_history', 'allergies', 'chronic_diseases', 'current_medications'];
    }

    /**
     * Search filter - searches in patient data and related user data
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function (Builder $q) use ($value) {
            $q
                // Search in user fields
                ->whereHas('user', function (Builder $userQuery) use ($value) {
                    $userQuery
                        ->fullName($value)
                        ->orWhere('email', 'like', '%' . $value . '%')
                        ->orWhere('phone', 'like', '%' . $value . '%')
                        ->orWhere('username', 'like', '%' . $value . '%');
                })
                // Search in patient fields
                ->orWhere('medical_history', 'like', '%' . $value . '%')
                ->orWhere('allergies', 'like', '%' . $value . '%')
                ->orWhere('chronic_diseases', 'like', '%' . $value . '%')
                ->orWhere('current_medications', 'like', '%' . $value . '%')
                ->orWhere('blood_type', 'like', '%' . $value . '%')
                ->orWhere('insurance_provider', 'like', '%' . $value . '%')
                ->orWhere('emergency_contact_name', 'like', '%' . $value . '%')
                ->orWhere('emergency_contact_phone', 'like', '%' . $value . '%');

        });
    }

    /**
     * Filter by blood type
     */
    protected function filterBloodType(Builder $query, string $value): Builder
    {
        return $query->where('blood_type', $value);
    }

    /**
     * Filter by gender (from user table)
     */
    protected function filterGender(Builder $query, string $value): Builder
    {
        return $query->whereHas('user', function (Builder $q) use ($value) {
            $q->where('gender', $value);
        });
    }

    /**
     * Filter by age range
     */
    protected function filterAgeRange(Builder $query, string $value): Builder
    {
        $ranges = [
            '0-18' => [0, 18],
            '19-35' => [19, 35],
            '36-50' => [36, 50],
            '51-65' => [51, 65],
            '65+' => [65, 150]
        ];

        if (!isset($ranges[$value])) {
            return $query;
        }

        [$minAge, $maxAge] = $ranges[$value];

        $minDate = Carbon::now()->subYears($maxAge)->format('Y-m-d');
        $maxDate = Carbon::now()->subYears($minAge)->format('Y-m-d');

        return $query->whereHas('user', function (Builder $q) use ($minDate, $maxDate) {
            $q->whereBetween('birthdate', [$minDate, $maxDate]);
        });
    }

    /**
     * Filter by insurance provider
     */
    protected function filterInsuranceProvider(Builder $query, string $value): Builder
    {
        return $query->where('insurance_provider', $value);
    }

    /**
     * Filter patients who have insurance
     */
    protected function filterHasInsurance(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereNotNull('insurance_provider');
        }

        return $query->whereNull('insurance_provider');
    }

    /**
     * Filter patients with expired insurance
     */
    protected function filterInsuranceExpired(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereNotNull('insurance_expiry_date')
                ->where('insurance_expiry_date', '<', Carbon::now());
        }

        return $query->where(function ($q) {
            $q->whereNull('insurance_expiry_date')
                ->orWhere('insurance_expiry_date', '>=', Carbon::now());
        });
    }

    /**
     * Filter patients who have allergies
     */
    protected function filterHasAllergies(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereHas('patientAllergies');
        }

        return $query->whereDoesntHave('patientAllergies');
    }

    /**
     * Filter patients who have chronic diseases
     */
    protected function filterHasChronicDiseases(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereNotNull('chronic_diseases')
                ->where('chronic_diseases', '!=', '');
        }

        return $query->where(function ($q) {
            $q->whereNull('chronic_diseases')
                ->orWhere('chronic_diseases', '');
        });
    }

    /**
     * Filter patients who have active medications
     */
    protected function filterHasActiveMedications(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereHas('medications', function (Builder $q) {
                $q->where('is_active', true)
                    ->where(function ($inner) {
                        $inner->whereNull('end_date')
                            ->orWhere('end_date', '>=', Carbon::now()->toDateString());
                    });
            });
        }

        return $query->whereDoesntHave('medications', function (Builder $q) {
            $q->where('is_active', true)
                ->where(function ($inner) {
                    $inner->whereNull('end_date')
                        ->orWhere('end_date', '>=', Carbon::now()->toDateString());
                });
        });
    }

    /**
     * Filter patients who have upcoming appointments
     */
    protected function filterHasUpcomingAppointments(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereHas('appointments', function (Builder $q) {
                $q->where('start_time', '>', Carbon::now())
                    ->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled']);
            });
        }

        return $query->whereDoesntHave('appointments', function (Builder $q) {
            $q->where('start_time', '>', Carbon::now())
                ->whereIn('appointment_status', ['pending', 'confirmed', 'rescheduled']);
        });
    }

    /**
     * Filter by emergency contact existence
     */
    protected function filterEmergencyContact(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereNotNull('emergency_contact_name')
                ->whereNotNull('emergency_contact_phone');
        }

        return $query->where(function ($q) {
            $q->whereNull('emergency_contact_name')
                ->orWhereNull('emergency_contact_phone');
        });
    }

    /**
     * Filter by height range
     */
    protected function filterHeightRange(Builder $query, array $value): Builder
    {
        if (isset($value['min'])) {
            $query->where('height', '>=', $value['min']);
        }

        if (isset($value['max'])) {
            $query->where('height', '<=', $value['max']);
        }

        return $query;
    }

    /**
     * Filter by weight range
     */
    protected function filterWeightRange(Builder $query, array $value): Builder
    {
        if (isset($value['min'])) {
            $query->where('weight', '>=', $value['min']);
        }

        if (isset($value['max'])) {
            $query->where('weight', '<=', $value['max']);
        }

        return $query;
    }

    /**
     * Filter by BMI category
     */
    protected function filterBmiCategory(Builder $query, string $value): Builder
    {
        $ranges = [
            'underweight' => [0, 18.5],
            'normal' => [18.5, 25],
            'overweight' => [25, 30],
            'obese' => [30, 100]
        ];

        if (!isset($ranges[$value])) {
            return $query;
        }

        [$minBmi, $maxBmi] = $ranges[$value];

        return $query->whereNotNull('height')
            ->whereNotNull('weight')
            ->whereRaw('(weight / POWER(height/100, 2)) BETWEEN ? AND ?', [$minBmi, $maxBmi]);
    }

    /**
     * Filter by user active status
     */
    protected function filterIsActive(Builder $query, $value): Builder
    {
        return $query->whereHas('user', function (Builder $q) use ($value) {
            $q->where('status', $value ? 'active' : '!=', 'active');
        });
    }

    /**
     * Filter by patient creation date range
     */
    protected function filterCreatedDateRange(Builder $query, array $value): Builder
    {
        if (isset($value['from'])) {
            $query->whereDate('created_at', '>=', $value['from']);
        }

        if (isset($value['to'])) {
            $query->whereDate('created_at', '<=', $value['to']);
        }

        return $query;
    }

    /**
     * Filter by last appointment date
     */
    protected function filterLastAppointmentDate(Builder $query, array $value): Builder
    {
        if (isset($value['from']) || isset($value['to'])) {
            $query->whereHas('appointments', function (Builder $q) use ($value) {
                if (isset($value['from'])) {
                    $q->whereDate('start_time', '>=', $value['from']);
                }
                if (isset($value['to'])) {
                    $q->whereDate('start_time', '<=', $value['to']);
                }
            });
        }

        return $query;
    }

    /**
     * Default sort configuration
     */
    protected string $defaultSortColumn = 'created_at';
    protected string $defaultSortDirection = 'desc';

    /**
     * Enable common filters on initialization
     */
    public function __construct($request)
    {
        parent::__construct($request);
        $this->enableSearchFilter();
        $this->enableCommonFilters();
    }
}
