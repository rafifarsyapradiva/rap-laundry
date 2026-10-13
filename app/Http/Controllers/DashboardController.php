<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama dashboard operasional kasir dan administrator.
     */
    public function index(DashboardService $dashboardService): View
    {
        $metrics = $dashboardService->getMetrics();
        $recentOrders = $dashboardService->getRecentOrders(10);
        $weeklyTrend = $dashboardService->getWeeklyTrend();
        $statusDistribution = $dashboardService->getStatusDistribution();

        return view('dashboard.index', compact(
            'metrics',
            'recentOrders',
            'weeklyTrend',
            'statusDistribution'
        ));
    }
}
