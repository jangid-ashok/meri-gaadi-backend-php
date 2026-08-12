@if (isset($mediaItems) && count($mediaItems) > 0)
    @foreach ($mediaItems as $media)
        <div class="col-6 col-md-4 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-2 text-center">
                    @if ($media->isVideo())
                        <video class="w-100 rounded" style="max-height: 160px; object-fit: cover;" controls preload="metadata">
                            <source src="{{ $media->url }}" type="{{ $media->mime }}">
                            Your browser does not support the video tag.
                        </video>
                    @else
                        <img src="{{ $media->url }}" alt="{{ $media->displayName() }}" class="img-fluid rounded" style="max-height: 160px; object-fit: cover; width: 100%;">
                    @endif
                    <p class="small text-muted mt-2 mb-1 text-truncate" title="{{ $media->displayName() }}">{{ $media->displayName() }}</p>
                    <span class="badge {{ $media->isVideo() ? 'bg-label-info' : 'bg-label-primary' }} me-1">{{ $media->isVideo() ? 'Video' : 'Image' }}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill mt-2 gallery-delete-btn" data-id="{{ $media->id }}">
                        <i class="bx bx-trash"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="col-12">
        <p class="text-center text-muted mb-0 py-4">No gallery items yet. Upload images or videos above.</p>
    </div>
@endif
