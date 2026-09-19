@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="h3 mb-1">{{ $variant->full_name }} specifications</h1>
            <p class="text-body-secondary mb-0">Manage the specification values for this variant.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.variants.show', $variant) }}">Back to variant</a>
            @if (Auth::user()->hasPermission('specifications.create') || Auth::user()->hasPermission('specifications.update'))
                <button class="btn btn-primary" type="submit" form="variant-specifications-form">Save changes</button>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div id="spec-form-alert" class="alert d-none" role="alert"></div>

    @if ($categories->isEmpty())
        <div class="card">
            <div class="card-body text-center py-6">
                <h2 class="h5">No specification categories available</h2>
                <p class="text-body-secondary mb-0">Create definition categories before assigning values to this variant.</p>
            </div>
        </div>
    @else
        <form id="variant-specifications-form" method="POST" class="row g-4">
            @csrf
            @foreach ($categories as $category)
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="h5 mb-0">{{ $category->name }}</h2></div>
                        <div class="card-body">
                            @php $defs = $category->definitions ?? collect(); @endphp
                            @if ($defs->isEmpty())
                                <p class="text-body-secondary mb-0">No definitions in this category yet.</p>
                            @else
                                <div class="row g-3">
                                    @foreach ($defs as $definition)
                                        @php $currentValue = $values->get($category->id, collect())->first(fn ($spec) => $spec->specification_definition_id === $definition->id)?->value ?? ''; @endphp
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label" for="spec-{{ $definition->id }}">{{ $definition->name }} @if($definition->unit) <span class="text-body-secondary">({{ $definition->unit }})</span> @endif</label>
                                            @if ($definition->data_type === 'boolean')
                                                <select class="form-select" id="spec-{{ $definition->id }}" name="spec-{{ $definition->id }}" data-definition-id="{{ $definition->id }}" data-data-type="boolean">
                                                    <option value="">Not set</option>
                                                    <option value="true" @selected($currentValue === 'true')>True</option>
                                                    <option value="false" @selected($currentValue === 'false')>False</option>
                                                </select>
                                            @elseif ($definition->data_type === 'select')
                                                <select class="form-select" id="spec-{{ $definition->id }}" name="spec-{{ $definition->id }}" data-definition-id="{{ $definition->id }}" data-data-type="select">
                                                    <option value="">Not set</option>
                                                    @foreach (($definition->options ?? []) as $option)
                                                        <option value="{{ $option }}" @selected($currentValue === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input class="form-control" id="spec-{{ $definition->id }}" name="spec-{{ $definition->id }}" value="{{ $currentValue }}" data-definition-id="{{ $definition->id }}" data-data-type="{{ $definition->data_type }}" placeholder="{{ $definition->unit ?: ucfirst($definition->data_type) }}">
                                            @endif
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
  const alertEl = document.getElementById('spec-form-alert');
  const form = document.getElementById('variant-specifications-form');
  const apiUrl = '/api/admin/variants/{{ $variant->id }}/specifications';

  form?.addEventListener('submit', async function (event) {
      event.preventDefault();
      const payload = [];
      const elements = form.querySelectorAll('[data-definition-id]');

      elements.forEach((element) => {
          const value = element.value;
          if (value === '') {
              return;
          }

          payload.push({
              specification_definition_id: Number(element.dataset.definitionId),
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
              const firstError = result?.errors ? Object.values(result.errors).flat()[0] : (result?.message || 'Unable to save specification values.');
              throw new Error(firstError);
          }

          alertEl.className = 'alert alert-success';
          alertEl.textContent = 'Specification values saved successfully.';
          window.location.reload();
      } catch (error) {
          alertEl.className = 'alert alert-danger';
          alertEl.textContent = error.message || 'Unable to save specification values.';
      }
  });
</script>
@endsection
