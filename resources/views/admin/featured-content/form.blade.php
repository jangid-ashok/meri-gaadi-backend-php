@extends('admin.layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y"><h4 class="fw-bold py-3">{{ $page_title }}</h4>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $item->exists ? route('admin.featured-content.update',$item) : route('admin.featured-content.store') }}">
        @csrf @if($item->exists)@method('PUT')@endif
        <div class="card"><div class="card-body row g-3">
            <div class="col-md-8"><label class="form-label" for="title">Title *</label><input class="form-control @error('title') is-invalid @enderror" id="title" name="title" required maxlength="255" value="{{ old('title',$item->title) }}">@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="placement">Placement *</label><select class="form-select" id="placement" name="placement" required>@foreach($placements as $placement)<option value="{{ $placement }}" @selected(old('placement',$item->placement)===$placement)>{{ str_replace('_',' ',ucfirst($placement)) }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description',$item->description) }}</textarea></div>
            <div class="col-12"><label class="form-label">Image *</label>@if($item->media)<div class="mb-2"><img src="{{ $item->media->url }}" alt="Current banner image" style="max-width:320px;max-height:180px;object-fit:cover" class="rounded"></div>@endif @if(Auth::user()->hasPermission('media.view'))@include('admin.shared.media-picker',['field'=>'media_id','value'=>$item->media_id])@else<input type="hidden" name="media_id" value="{{ $item->media_id }}"><div class="alert alert-warning">Media Library access is required to change the image.</div>@endif @error('media_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="cta_text">CTA text</label><input class="form-control" id="cta_text" name="cta_text" maxlength="100" value="{{ old('cta_text',$item->cta_text) }}"></div>
            <div class="col-md-6"><label class="form-label" for="cta_url">CTA URL</label><input class="form-control" id="cta_url" name="cta_url" maxlength="2048" value="{{ old('cta_url',$item->cta_url) }}"></div>
            <div class="col-md-4"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" @selected(old('status',$item->status)==='active')>Active</option><option value="inactive" @selected(old('status',$item->status)==='inactive')>Inactive</option></select></div>
            <div class="col-md-4"><label class="form-label" for="display_order">Display order</label><input class="form-control" type="number" id="display_order" name="display_order" min="0" max="65535" required value="{{ old('display_order',$item->display_order ?? 0) }}"></div>
        </div><div class="card-footer d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-outline-secondary" href="{{ route('admin.featured-content.index') }}">Cancel</a></div></div>
    </form>
</div>
@endsection
