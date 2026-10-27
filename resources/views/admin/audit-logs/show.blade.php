@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-5"><div><h1 class="h3 mb-1">Audit Log #{{ $log->id }}</h1><p class="text-body-secondary mb-0">Read-only event details</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.audit-logs.index') }}">Back to logs</a></div>
    <div class="card mb-4"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">User</dt><dd class="col-sm-9">{{ $log->user?->name ?? 'System / unavailable' }}{{ $log->user?->email ? ' ('.$log->user->email.')' : '' }}</dd>
        <dt class="col-sm-3">Action</dt><dd class="col-sm-9">{{ ucfirst(str_replace('_',' ',$log->action)) }}</dd>
        <dt class="col-sm-3">Resource</dt><dd class="col-sm-9">{{ $log->resource }}{{ $log->auditable_id ? ' #'.$log->auditable_id : '' }}</dd>
        <dt class="col-sm-3">Record</dt><dd class="col-sm-9">{{ $log->record_label ?? '—' }}</dd>
        <dt class="col-sm-3">Date</dt><dd class="col-sm-9">{{ $log->created_at?->format('d M Y, h:i:s A') }}</dd>
        <dt class="col-sm-3">IP address</dt><dd class="col-sm-9">{{ $log->ip_address ?? '—' }}</dd>
        <dt class="col-sm-3">User agent</dt><dd class="col-sm-9 text-break">{{ $log->user_agent ?? '—' }}</dd>
        @if($log->request_id)<dt class="col-sm-3">Request ID</dt><dd class="col-sm-9">{{ $log->request_id }}</dd>@endif
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $log->description }}</dd>
    </dl></div></div>
    @if(count($log->old_values ?? []) || count($log->new_values ?? []))
    <div class="card"><div class="card-header"><h2 class="h5 mb-0">{{ $log->action === 'update' ? 'Changes' : ucfirst($log->action).' data' }}</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Field</th><th>Old value</th><th>New value</th></tr></thead><tbody>
    @foreach(array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? []))) as $field)<tr><th>{{ str($field)->replace('_',' ')->headline() }}</th><td><pre class="mb-0 text-wrap">{{ is_array($log->old_values[$field] ?? null) ? json_encode($log->old_values[$field], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : ($log->old_values[$field] ?? '—') }}</pre></td><td><pre class="mb-0 text-wrap">{{ is_array($log->new_values[$field] ?? null) ? json_encode($log->new_values[$field], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : ($log->new_values[$field] ?? '—') }}</pre></td></tr>@endforeach
    </tbody></table></div></div>
    @endif
</main>
@endsection
