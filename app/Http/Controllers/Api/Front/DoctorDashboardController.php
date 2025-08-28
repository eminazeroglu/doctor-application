<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Services\Module\DoctorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DoctorDashboardController extends Controller
{
    public function __construct(
        protected DoctorDashboardService $service
    ) {}

    /**
     * Üst sağ: Performance overview + qısa KPI-lar
     * Query: ?from=YYYY-MM-DD&to=YYYY-MM-DD (optional; default = son 7 gün)
     */
    public function summary(Request $request): JsonResponse
    {
        [$from, $to] = $this->service->resolveDateRange($request->get('from'), $request->get('to'), days: 7);

        return response()->json($this->service->getSummary(auth()->id(), $from, $to));
    }

    /**
     * Sol üst: Performance since last statement (chart)
     * Query: ?from=YYYY-MM-DD&to=YYYY-MM-DD&interval=month|week (default: month)
     */
    public function performance(Request $request): JsonResponse
    {
        [$from, $to] = $this->service->resolveDateRange($request->get('from'), $request->get('to'), months: 6);
        $interval = $request->get('interval', 'month');

        return response()->json($this->service->getPerformance(auth()->id(), $from, $to, $interval));
    }

    /**
     * Sol alt: Appointment report (cədvəl)
     * Query: ?from=YYYY-MM-DD&to=YYYY-MM-DD (default: son ay)
     */
    public function appointmentReport(Request $request): JsonResponse
    {
        [$from, $to] = $this->service->resolveDateRange($request->get('from'), $request->get('to'), months: 1);

        return response()->json($this->service->getAppointmentReport(auth()->id(), $from, $to));
    }

    /**
     * Sağ alt: Recent booking details
     * Query: ?limit=10 (default: 10)
     */
    public function recentBookings(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);

        return response()->json($this->service->getRecentBookings(auth()->id(), $limit));
    }

    /**
     * Download düyməsi: appointment report-u xlsx/csv çıxarır.
     * GET /api/doctor/dashboard/appointments/report/export?from=...&to=...&format=xlsx|csv
     */
    public function exportAppointmentReport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->service->resolveDateRange($request->get('from'), $request->get('to'), months: 1);
        $format = in_array($request->get('format'), ['xlsx','csv']) ? $request->get('format') : 'xlsx';

        [$headings, $rows] = $this->service->buildAppointmentReportForExport(auth()->id(), $from, $to);

        // Sadə anonim export (FromArray)
        $export = new class($headings, $rows) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(private readonly array $headings, private array $rows) {}
            public function array(): array { return $this->rows; }
            public function headings(): array { return $this->headings; }
        };

        $filename = sprintf('appointment_report_%s_%s.%s', $from->toDateString(), $to->toDateString(), $format);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }
        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}
