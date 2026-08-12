<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class DashboardController extends Controller {

    public function index() {
        $data = array();
        $data['page_title'] = 'Dashboard';
        $data['leftMenuActive'] = 'dashboard';
        return view('admin.dashboard', $data);
    }
}