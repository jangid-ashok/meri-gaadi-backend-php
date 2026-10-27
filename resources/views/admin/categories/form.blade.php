@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-6"><div><h1 class="h3 mb-1">{{ $category->exists ? 'Edit category' : 'Add category' }}</h1><p class="text-body-secondary mb-0">Set the category hierarchy and usage type.</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">Back to categories</a></div>
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    <div class="card"><div class="card-body"><form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="row g-4" enctype="multipart/form-data" id="category-form">@csrf @if($category->exists)@method('PUT')@endif
        <div class="col-md-6"><label class="form-label" for="name">Name <span class="text-danger">*</span></label><input class="form-control" id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="255"></div>
        <div class="col-md-3"><label class="form-label" for="category_type">Category type <span class="text-danger">*</span></label><select class="form-select" id="category_type" name="category_type" required>@foreach($categoryTypes as $value => $label)<option value="{{ $value }}" @selected(old('category_type', $category->category_type ?: 'automotive') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" @selected(old('status', $category->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $category->status) === 'inactive')>Inactive</option></select></div>
        <div class="col-md-8"><label class="form-label" for="parent_id">Parent category</label><select class="form-select" id="parent_id" name="parent_id"><option value="">Root category</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" data-type="{{ $parent->category_type }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name }} ({{ $categoryTypes[$parent->category_type] ?? $parent->category_type }})</option>@endforeach</select><div class="form-text">Only active categories of the same type can be parents.</div></div>
        <div class="col-md-4"><label class="form-label" for="sort_order">Sort order</label><input class="form-control" id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0"></div>
        <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="5" maxlength="5000">{{ old('description', $category->description) }}</textarea></div>
        <div class="col-12">
            <label class="form-label" for="image">Category image <span class="text-body-secondary fw-normal">(optional)</span></label>
            <input class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,image/jpeg,image/png,image/gif,image/webp,image/avif" aria-describedby="image-help">
            <div class="form-text" id="image-help">JPG, PNG, GIF, WebP, or AVIF; maximum {{ (int) ceil(config('media.max_image_kb', 5120) / 1024) }} MB.</div>
            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @if($category->image)
                <div class="mt-3" id="current-image-preview">
                    <div class="small text-body-secondary mb-2">Current image</div>
                    <img src="{{ media_image_url($category->image, 'media') }}" alt="Current image for {{ $category->name }}" class="img-thumbnail" style="max-width: 100%; max-height: 220px; object-fit: contain;">
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1" @checked(old('remove_image'))>
                    <label class="form-check-label" for="remove_image">Remove current image</label>
                </div>
            @endif
            <div class="mt-3" id="selected-image-preview" hidden>
                <div class="small text-body-secondary mb-2">New image preview</div>
                <img id="selected-image" alt="Selected category image preview" class="img-thumbnail" style="max-width: 100%; max-height: 220px; object-fit: contain;">
            </div>
        </div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit" id="category-submit" data-saving-label="Saving…">{{ $category->exists ? 'Save changes' : 'Create category' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">Cancel</a></div>
    </form></div></div>
</main>
<script>
    document.getElementById('category_type')?.addEventListener('change', function () {
        document.querySelectorAll('#parent_id option[data-type]').forEach(option => { option.hidden = option.dataset.type !== this.value; });
        const selected = document.querySelector('#parent_id option:checked');
        if (selected?.hidden) document.getElementById('parent_id').value = '';
    });
    document.getElementById('category_type')?.dispatchEvent(new Event('change'));

    const imageInput = document.getElementById('image');
    const currentPreview = document.getElementById('current-image-preview');
    const selectedPreview = document.getElementById('selected-image-preview');
    const removeImage = document.getElementById('remove_image');
    const previewImage = document.getElementById('selected-image');
    let previewUrl;

    imageInput?.addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file) {
            selectedPreview.hidden = true;
            currentPreview?.classList.remove('d-none');
            return;
        }
        if (removeImage) removeImage.checked = false;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        previewImage.src = previewUrl;
        selectedPreview.hidden = false;
        currentPreview?.classList.add('d-none');
    });

    removeImage?.addEventListener('change', function () {
        if (this.checked) {
            imageInput.value = '';
            selectedPreview.hidden = true;
            currentPreview?.classList.add('d-none');
        } else {
            currentPreview?.classList.remove('d-none');
        }
    });
    if (removeImage?.checked) currentPreview?.classList.add('d-none');

    document.getElementById('category-form')?.addEventListener('submit', function () {
        const button = document.getElementById('category-submit');
        button.disabled = true;
        button.textContent = button.dataset.savingLabel;
    });
</script>
@endsection