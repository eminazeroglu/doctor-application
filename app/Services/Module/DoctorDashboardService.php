<?php

namespace App\Services\Module;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DoctorDashboardService
{
    /**
     * Tarix aralığını həll edir (default parametr: days|weeks|months-dan biri)
     */
    public function resolveDateRange(?string $from, ?string $to, int $days = 0, int $weeks = 0, int $months = 0): array
    {
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        if ($from) {
            $fromDate = Carbon::parse($from)->startOfDay();
        } else {
            if ($months > 0) $fromDate = $toDate->copy()->subMonths($months)->startOfDay();
            elseif ($weeks > 0) $fromDate = $toDate->copy()->subWeeks($weeks)->startOfDay();
            else $fromDate = $toDate->copy()->subDays($days)->startOfDay();
        }

        return [$fromDate, $toDate];
    }

    /**
     * Üst sağ bölmə: yeni vs mövcud xəstələr faizi, ümumi sürətli KPI-lar
     */
    public function getSummary(int $userId, Carbon $from, Carbon $to): array
    {
        $doctorId = $this->doctorIdByUser($userId);

        // Bu aralıqda görüşlər
        $appointments = DB::table('appointments')
            ->select('id', 'patient_id', 'start_time', 'appointment_status')
            ->where('doctor_id', $doctorId)
            ->whereBetween('start_time', [$from, $to])
            ->get();

        $total = $appointments->count();

        // Yeni xəstə: həmin pasiyentin ən ilk görüşü bu intervaldadır
        $patientIds = $appointments->pluck('patient_id')->unique()->values();

        $firstAppointments = DB::table('appointments')
            ->select('patient_id', DB::raw('MIN(start_time) as first_at'))
            ->where('doctor_id', $doctorId)
            ->whereIn('patient_id', $patientIds)
            ->groupBy('patient_id')
            ->get()
            ->keyBy('patient_id');

        $newPatients = 0;
        foreach ($patientIds as $pid) {
            $firstAt = Carbon::parse($firstAppointments[$pid]->first_at ?? null);
            if ($firstAt->between($from, $to)) $newPatients++;
        }

        $existingPatientsAppointments = $total - $newPatients;

        $newPct = $total > 0 ? round(($newPatients / $total) * 100) : 0;
        $existingPct = 100 - $newPct;

        return [
            'range' => [$from->toDateString(), $to->toDateString()],
            'overview' => [
                'new_patients_percent' => $newPct,
                'existing_patients_percent' => $existingPct,
            ],
            'kpis' => [
                'total_appointments' => $total,
                'unique_patients'    => $patientIds->count(),
                'new_patients'       => $newPatients,
            ]
        ];
    }

    /**
     * Sol üst chart: interval = 'month' | 'week'
     * cavabda iki seriya qaytarırıq: appointments və new_patients
     */
    public function getPerformance(int $userId, Carbon $from, Carbon $to, string $interval = 'month'): array
    {
        $doctorId = $this->doctorIdByUser($userId);

        // Qruplama formatı
        $format = $interval === 'week' ? '%x-%v' : '%Y-%m'; // ISO week / month

        $rows = DB::table('appointments')
            ->select(
                DB::raw("DATE_FORMAT(start_time, '{$format}') as bucket"),
                DB::raw('COUNT(*) as total_appointments'),
                DB::raw('COUNT(DISTINCT patient_id) as unique_patients')
            )
            ->where('doctor_id', $doctorId)
            ->whereBetween('start_time', [$from, $to])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        // Yeni xəstələri hesablamaq üçün həmin bucket-in daxilində birinci görüşü düşənlər
        $firsts = DB::table('appointments')
            ->select(
                DB::raw("DATE_FORMAT(MIN(start_time), '{$format}') as bucket"),
                'patient_id'
            )
            ->where('doctor_id', $doctorId)
            ->groupBy('patient_id')
            ->get()
            ->groupBy('bucket'); // bucket => kolleksiya(patient_id)

        // Periodu düz array olaraq tikək
        $period = $this->buckets($from, $to, $interval);

        $series = [];
        foreach ($period as $b) {
            $appointments = (int) ($rows[$b]->total_appointments ?? 0);
            $newPatients  = isset($firsts[$b]) ? $firsts[$b]->count() : 0;

            $series[] = [
                'bucket' => $b,
                'appointments' => $appointments,
                'new_patients' => $newPatients,
            ];
        }

        return [
            'range' => [$from->toDateString(), $to->toDateString()],
            'interval' => $interval,
            'series' => $series,
        ];
    }

    /**
     * Sol alt cədvəl: statuslara görə saylar
     */
    public function getAppointmentReport(int $userId, Carbon $from, Carbon $to): array
    {
        $doctorId = $this->doctorIdByUser($userId);

        $statuses = AppointmentStatusEnum::getValues(); // bütün mövcud statusların siyahısı

        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = DB::table('appointments')
                ->where('doctor_id', $doctorId)
                ->where('appointment_status', $status)
                ->whereBetween('start_time', [$from, $to])
                ->count();
        }

        return [
            'range'  => [$from->toDateString(), $to->toDateString()],
            'totals' => $result,
        ];
    }

    /**
     * Son rezervlər
     */
    public function getRecentBookings(int $userId, int $limit = 10): array
    {
        $doctorId = $this->doctorIdByUser($userId);

        $items = Appointment::query()
            ->select(['id', 'doctor_id', 'patient_id', 'start_time', 'end_time'])
            ->with([
                'patient.user:id,name,surname' // lazım olan user sahələrini yükləyirik
            ])
            ->where('doctor_id', $doctorId)
            ->orderByDesc('start_time')
            ->limit($limit)
            ->get()
            ->map(function ($appointment) {
                $patientUser = $appointment->patient?->user;

                return [
                    'id'           => $appointment->id,
                    'time_range'   => Carbon::parse($appointment->start_time)->format('H:i')
                        . ' - ' . Carbon::parse($appointment->end_time)->format('H:i'),
                    'date_human'   => Carbon::parse($appointment->start_time)->isoFormat('dddd, MMM D'),
                    'patient_name' => $patientUser->full_name,
                ];
            })
            ->values();

        return ['items' => $items];
    }

    /**
     * Köməkçi: user_id → doctor_id xəritəsi
     * Layihənizdə həkim user-lə əlaqəlidirsə bu metodu uyğunlaşdırın.
     */
    protected function doctorIdByUser(int $userId): int
    {
        // Ən sadə: doctors cədvəlində user_id saxlanılır
        return (int) DB::table('doctors')->where('user_id', $userId)->value('id');
    }

    /**
     * Bucket siyahısı (month|week)
     */
    protected function buckets(Carbon $from, Carbon $to, string $interval = 'month'): array
    {
        $out = [];
        if ($interval === 'week') {
            $period = CarbonPeriod::create($from->copy()->startOfWeek(), '1 week', $to);
            foreach ($period as $d) $out[] = $d->format('o-\WW'); // ISO week label
        } else {
            $period = CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to);
            foreach ($period as $d) $out[] = $d->format('Y-m');
        }
        return $out;
    }

    /**
     * EXPORT üçün tablo hazırla: başlıqlar + sətirlər
     */
    public function buildAppointmentReportForExport(int $userId, Carbon $from, Carbon $to): array
    {
        $report = $this->getAppointmentReport($userId, $from, $to);

        // Headings: Date From, Date To, sonra enum status adları
        $headings = array_merge(
            ['from', 'to'],
            array_keys($report['totals'])
        );

        // Row: tarix aralığı + status dəyərləri sırayla
        $row = array_merge(
            [$report['range'][0], $report['range'][1]],
            array_values($report['totals'])
        );

        return [$headings, [$row]];
    }
}
