@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="h3 mb-1">{{ $variant->full_name }} features</h1>
            <p class="text-body-secondary mb-0">Manage the standard feature values for this variant.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.variants.show', $variant) }}">Back to variant</a>
            @if (Auth::user()->hasPermission('features.create') || Auth::user()->hasPermission('features.update'))
                <button class="btn btn-primary" type="submit" form="variant-features-form">Save changes</button>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div id="feature-form-alert" class="alert d-none" role="alert"></div>

    @if ($categories->isEmpty())
        <div class="card">
            <div class="card-body text-center py-6">
                <h2 class="h5">No feature categories available</h2>
                <p class="text-body-secondary mb-0">Create feature categories before assigning values to this variant.</p>
            </div>
        </div>
    @else
        <form id="variant-features-form" method="POST" class="row g-4">
            @csrf
            @foreach ($categories as $category)
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="h5 mb-0">{{ $category->name }}</h2></div>
                        <div class="card-body">
                            @php $defs = $category->features ?? collect(); @endphp
                            @if ($defs->isEmpty())
                                <p class="text-body-secondary mb-0">No features in this category yet.</p>
                            @else
                                <div class="row g-3">
                                    @foreach ($defs as $feature)
                                        @php $currentValue = $values->get($category->id, collect())->first(fn ($entry) => $entry->feature_id === $feature->id)?->value ?? ''; @endphp
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label" for="feature-{{ $feature->id }}">{{ $feature->name }}</label>
                                            <select class="form-select" id="feature-{{ $feature->id }}" name="feature-{{ $feature->id }}" data-feature-id="{{ $feature->id }}">
                                                <option value="">Not set</option>
                                                <option value="true" @selected($currentValue === 'true')>True</option>
                                                <option value="false" @selected($currentValue === 'false')>False</option>
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </form>
    @endif
</main>

<script>
  const alertEl = document.getElementById('feature-form-alert');
  const form = document.getElementById('variant-features-form');
  const apiUrl = '/api/admin/variants/{{ $variant->id }}/features';

  form?.addEventListener('submit', async function (event) {
      event.preventDefault();
      const payload = [];
      const elements = form.querySelectorAll('[data-feature-id]');

      elements.forEach((element) => {
          const value = element.value;
          if (value === '') {
              return;
          }

          payload.push({
              feature_id: Number(element.dataset.featureId),
              value: value,
          });
      });

      alertEl.className = 'alert d-none';
      alertEl.textContent = '';

      try {
          const response = await fetch(apiUrl, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
              },
              body: JSON.stringify(payload),
          });

          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
              const firstError = result?.errors ? Object.values(result.errors).flat()[0] : (result?.message || 'Unable to save feature values.');
              throw new Error(firstError);
          }

          alertEl.className = 'alert alert-success';
          alertEl.textContent = 'Feature values saved successfully.';
          window.location.reload();
      } catch (error) {
          alertEl.className = 'alert alert-danger';
          alertEl.textContent = error.message || 'Unable to save feature values.';
      }
  });
</script>
@endsection
