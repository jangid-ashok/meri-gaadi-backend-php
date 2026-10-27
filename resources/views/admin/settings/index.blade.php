@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="h3 mb-1">Settings</h1>
            <p class="text-body-secondary mb-0">Update application-wide configuration values.</p>
        </div>
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card">
        <div class="card-body border-bottom">
            <div class="nav nav-pills flex-wrap gap-2" role="tablist">
                @foreach (['general', 'social', 'system'] as $group)
                    <a class="nav-link {{ request('group', 'general') === $group ? 'active' : '' }}" href="{{ route('admin.settings.index', ['group' => $group]) }}">{{ ucfirst($group) }}</a>
                @endforeach
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="row g-4">@csrf @method('PUT')
                @php $group = request('group', 'general'); $items = $settings->where('group', $group); @endphp
                @foreach ($items as $setting)
                    <div class="col-md-6">
                        <label class="form-label" for="{{ $setting->key }}">{{ Str::headline(str_replace('_', ' ', $setting->key)) }}</label>
                        @switch($setting->type)
                            @case('text')
                                <textarea class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}" rows="4">{{ old($setting->key, $setting->value) }}</textarea>
                                @break
                            @case('boolean')
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="{{ $setting->key }}" name="{{ $setting->key }}" value="1" @checked((bool) old($setting->key, $setting->value))>
                                </div>
                                @break
                            @case('integer')
                                <input class="form-control" id="{{ $setting->key }}" type="number" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}" min="10" max="100">
                                @break
                            @case('url')
                                <input class="form-control" id="{{ $setting->key }}" type="url" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}">
                                @break
                            @default
                                <input class="form-control" id="{{ $setting->key }}" type="text" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}">
                        @endswitch
                    </div>
                @endforeach
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save settings</button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
