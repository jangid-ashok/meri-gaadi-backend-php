@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div><h1 class="h3 mb-1">{{ $faq->exists ? 'Edit FAQ' : 'Add FAQ' }}</h1><p class="text-body-secondary mb-0">Create or update a frequently asked question.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.faqs.index') }}">Back to FAQs</a>
    </div>
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="row g-4">
                @csrf
                @if ($faq->exists) @method('PUT') @endif
                <div class="col-md-6"><label class="form-label" for="question">Question <span class="text-danger">*</span></label><input class="form-control" id="question" name="question" value="{{ old('question', $faq->question) }}" required maxlength="500"></div>
                <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" @selected(old('status', $faq->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $faq->status) === 'inactive')>Inactive</option></select></div>
                <div class="col-md-3"><label class="form-label" for="sort_order">Sort order</label><input class="form-control" id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" min="0"></div>
                <div class="col-md-6"><label class="form-label" for="category_id">Category</label><select class="form-select" id="category_id" name="category_id"><option value="">No category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $faq->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label" for="answer">Answer <span class="text-danger">*</span></label><textarea class="form-control" id="answer" name="answer" rows="8" maxlength="20000" required>{{ old('answer', $faq->answer) }}</textarea></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">{{ $faq->exists ? 'Save changes' : 'Create FAQ' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.faqs.index') }}">Cancel</a></div>
            </form>
        </div>
    </div>
</main>
@endsection
