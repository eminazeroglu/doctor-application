<?php

namespace App\Exports;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AppointmentExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Query for appointments
     */
    public function query()
    {
        $query = Appointment::query()
            ->with([
                'doctor.user',
                'doctor.category',
                'clinic',
                'service',
                'patient.user'
            ])
            ->where('patient_id', $this->filters['patient_id']);

        // Status filter
        if (!empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('appointment_status', $this->filters['status']);
        }

        // Date range filter
        if (!empty($this->filters['date_range'])) {
            $this->applyDateRangeFilter($query, $this->filters['date_range']);
        }

        return $query->orderBy('start_time', 'desc');
    }

    /**
     * Excel başlıqları
     */
    public function headings(): array
    {
        return [
            'Randevu ID',
            'Həkim',
            'İxtisas',
            'Klinika',
            'Xidmət',
            'Randevu Tarixi',
            'Randevu Saatı',
            'Müddət (dəq)',
            'Status',
            'Müraciət Səbəbi',
            'Qiymət (AZN)',
            'Ödəniş Statusu',
            'Konsultasiya Növü',
            'Yaradılma Tarixi',
            'Son Yenilənmə'
        ];
    }

    /**
     * Hər sətir üçün data mapping
     */
    public function map($appointment): array
    {
        return [
            $appointment->uuid,
            $appointment->doctor->user->fullname .
            ($appointment->doctor->title ? ' (' . $appointment->doctor->title . ')' : ''),
            $appointment->doctor->category?->name ?? '-',
            $appointment->clinic?->name ?? '-',
            $appointment->service?->name ?? '-',
            $appointment->start_time ? $appointment->start_time->format('d.m.Y') : '-',
            $appointment->start_time && $appointment->end_time
                ? $appointment->start_time->format('H:i') . ' - ' . $appointment->end_time->format('H:i')
                : '-',
            $appointment->duration ?? '-',
            $this->getStatusText($appointment->appointment_status),
            $appointment->complaint ?? '-',
            $appointment->price ? number_format($appointment->price, 2) : '0.00',
            $appointment->is_paid ? 'Ödənilib' : 'Ödənilməyib',
            $this->getConsultationTypeText($appointment->consultation_type),
            $appointment->created_at,
            $appointment->updated_at,
        ];
    }

    /**
     * Excel styling
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Başlıq sətiri styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '2F5233'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Status mətni
     */
    private function getStatusText(string $status): string
    {
        return AppointmentStatusEnum::getDescription($status);
    }

    /**
     * Konsultasiya növü mətni
     */
    private function getConsultationTypeText(string $type): string
    {
        return match($type) {
            'in_person' => 'Üz-üzə',
            'online' => 'Onlayn',
            'home_visit' => 'Ev ziyarəti',
            default => $type
        };
    }

    /**
     * Tarix aralığı filterini tətbiq edir
     */
    private function applyDateRangeFilter($query, string $dateRange): void
    {
        $now = now();

        switch ($dateRange) {
            case 'last_week':
                $query->whereBetween('start_time', [$now->copy()->subWeek(), $now]);
                break;
            case 'last_month':
                $query->whereBetween('start_time', [$now->copy()->subMonth(), $now]);
                break;
            case 'last_three_months':
                $query->whereBetween('start_time', [$now->copy()->subMonths(3), $now]);
                break;
            case 'last_six_months':
                $query->whereBetween('start_time', [$now->copy()->subMonths(6), $now]);
                break;
            case 'last_year':
                $query->whereBetween('start_time', [$now->copy()->subYear(), $now]);
                break;
            case 'all_time':
                // Heç bir məhdudiyyət tətbiq etmə
                break;
            default:
                // Default olaraq son 6 ay
                $query->whereBetween('start_time', [$now->copy()->subMonths(6), $now]);
        }
    }
}
