<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index()
    {
        return view('admin.dashboard', [
            'page_title' => 'Dashboard',
            'leftMenuActive' => 'dashboard',
        ]);
    }

    public function data()
    {
        return response()->json($this->dashboardService->summary());
    }
}