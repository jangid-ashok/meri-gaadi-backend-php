@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-5"><h1 class="h3 mb-1">Audit Logs</h1><p class="text-body-secondary mb-0">Read-only history of administrative changes.</p></div>
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-2"><label class="form-label" for="search">Search</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Description, record, user, or ID"></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="user_id">User</label><select id="user_id" name="user_id" class="form-select"><option value="">All users</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="action">Action</label><select id="action" name="action" class="form-select"><option value="">All actions</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ ucfirst(str_replace('_',' ',$action)) }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="resource">Resource</label><select id="resource" name="resource" class="form-select"><option value="">All resources</option>@foreach($resources as $resource)<option value="{{ $resource }}" @selected(($filters['resource'] ?? '') === $resource)>{{ $resource }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-1"><label class="form-label" for="from">From</label><input id="from" type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}"></div>
                <div class="col-6 col-lg-1"><label class="form-label" for="to">To</label><input id="to" type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}"></div>
                <div class="col-6 col-lg-1 d-flex gap-2"><button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-outline-secondary" href="{{ route('admin.audit-logs.index') }}">Clear</a></div>
            </form>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Date / Time</th><th>User</th><th>Action</th><th>Resource</th><th>Record</th><th>Description</th><th>IP Address</th><th></th></tr></thead><tbody>
        @forelse($logs as $log)<tr><td class="text-nowrap">{{ $log->created_at?->format('d M Y, h:i A') }}</td><td>{{ $log->user?->name ?? 'System / unavailable' }}</td><td><span class="badge bg-label-secondary">{{ ucfirst(str_replace('_',' ',$log->action)) }}</span></td><td>{{ $log->resource }}</td><td>{{ $log->record_label ?? ($log->auditable_id ? '#'.$log->auditable_id : '—') }}</td><td>{{ $log->description }}</td><td>{{ $log->ip_address ?? '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.audit-logs.show', $log) }}">Details</a></td></tr>
        @empty<tr><td colspan="8" class="text-center py-6"><h2 class="h5">No audit logs found.</h2><p class="text-body-secondary mb-0">Try changing the filters.</p></td></tr>@endforelse
        </tbody></table></div>
        @if($logs->total() > 0)<div class="card-footer">@include('admin.shared.pagination', ['paginator' => $logs])</div>@endif
    </div>
</main>
@endsection