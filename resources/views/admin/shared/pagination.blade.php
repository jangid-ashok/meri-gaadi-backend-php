@php
    $query = request()->except('page', 'per_page');
    $pageLinks = $paginator->linkCollection();
@endphp

@if ($paginator->total() > 0)
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div class="small text-body-secondary">
            Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </div>
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-end gap-3">
            <form method="GET" class="d-flex align-items-center gap-2">
                @foreach ($query as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $arrayValue)<input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">@endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label class="small text-body-secondary text-nowrap" for="per-page">Rows per page</label>
                <select class="form-select form-select-sm" id="per-page" name="per_page" onchange="this.form.submit()" aria-label="Rows per page">
                    @foreach ([10, 15, 20, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
            @if ($paginator->hasPages())
                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0 flex-wrap">
                        @foreach ($pageLinks as $link)
                            @if ($link['url'] === null)
                                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{!! $link['label'] !!}</span></li>
                            @elseif ($link['active'])
                                <li class="page-item active" aria-current="page"><span class="page-link">{!! $link['label'] !!}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $link['url'] }}">{!! $link['label'] !!}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </div>
@endif