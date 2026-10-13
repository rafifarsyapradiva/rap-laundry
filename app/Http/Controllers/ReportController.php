<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Tampilkan halaman dasbor laporan analitik operasional dan keuangan.
     */
    public function index(Request $request, ReportService $reportService): View
    {
        $startDateStr = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDateStr = $request->input('end_date', now()->format('Y-m-d'));

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        // Pastikan tanggal mulai tidak melebihi tanggal akhir
        if ($startDate->gt($endDate)) {
            $startDate = $endDate->copy()->startOfDay();
            $startDateStr = $startDate->format('Y-m-d');
        }

        $summary = $reportService->getSummaryMetrics($startDate, $endDate);
        $paymentMethods = $reportService->getPaymentMethodBreakdown($startDate, $endDate);
        $topServices = $reportService->getTopServices($startDate, $endDate, 8);
        $laundryStatusCounts = $reportService->getOrdersByLaundryStatus($startDate, $endDate);
        $paymentStatusCounts = $reportService->getOrdersByPaymentStatus($startDate, $endDate);
        $recentOrdersInPeriod = $reportService->getDetailedOrders($startDate, $endDate)->take(15);
        $recentPaymentsInPeriod = $reportService->getDetailedPayments($startDate, $endDate)->take(15);

        return view('reports.index', compact(
            'startDateStr',
            'endDateStr',
            'summary',
            'paymentMethods',
            'topServices',
            'laundryStatusCounts',
            'paymentStatusCounts',
            'recentOrdersInPeriod',
            'recentPaymentsInPeriod'
        ));
    }

    /**
     * Ekspor laporan transaksi pesanan ke format CSV yang aman.
     */
    public function exportOrdersCsv(Request $request, ReportService $reportService): StreamedResponse
    {
        $startDateStr = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDateStr = $request->input('end_date', now()->format('Y-m-d'));

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        if ($startDate->gt($endDate)) {
            $startDate = $endDate->copy()->startOfDay();
        }

        return $reportService->exportOrdersCsv($startDate, $endDate);
    }

    /**
     * Ekspor buku kas masuk penerimaan pembayaran ke format CSV yang aman.
     */
    public function exportPaymentsCsv(Request $request, ReportService $reportService): StreamedResponse
    {
        $startDateStr = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDateStr = $request->input('end_date', now()->format('Y-m-d'));

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        if ($startDate->gt($endDate)) {
            $startDate = $endDate->copy()->startOfDay();
        }

        return $reportService->exportPaymentsCsv($startDate, $endDate);
    }
}
