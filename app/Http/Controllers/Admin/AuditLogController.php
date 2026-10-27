<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->applyFilters($request, AuditLog::query())->with('user:id,name,email');
        $logs = $this->paginateRequest($request, $query->latest('created_at')->latest('id'));
        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'users' => User::whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())->orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'resources' => AuditLog::query()->select('resource')->distinct()->orderBy('resource')->pluck('resource'),
            'filters' => $request->only('search', 'user_id', 'action', 'resource', 'from', 'to'),
            'page_title' => 'Audit Logs', 'leftMenuActive' => 'audit-logs',
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        return view('admin.audit-logs.show', ['log' => $auditLog->load('user:id,name,email'), 'page_title' => 'Audit Log #'.$auditLog->id, 'leftMenuActive' => 'audit-logs']);
    }

    public function apiIndex(Request $request)
    {
        $query = $this->applyFilters($request, AuditLog::query())->with('user:id,name,email')->latest('created_at')->latest('id');
        return response()->json(['data' => $this->paginateRequest($request, $query)]);
    }

    public function apiShow(AuditLog $auditLog)
    {
        return response()->json(['data' => $auditLog->load('user:id,name,email')]);
    }

    private function applyFilters(Request $request, $query)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'], 'user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:32'], 'resource' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        foreach (['user_id', 'action', 'resource'] as $field) if ($request->filled($field)) $query->where($field, $request->input($field));
        if ($request->filled('from')) $query->where('created_at', '>=', $request->date('from')->startOfDay());
        if ($request->filled('to')) $query->where('created_at', '<', $request->date('to')->addDay()->startOfDay());
        if ($request->filled('search')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->input('search')).'%';
            $query->where(fn ($q) => $q->where('description', 'like', $term)->orWhere('resource', 'like', $term)->orWhere('record_label', 'like', $term)->orWhere('auditable_id', $request->input('search'))->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
        }
        return $query;
    }
}
