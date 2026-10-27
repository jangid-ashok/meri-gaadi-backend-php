@extends('admin.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Homepage Management</h4>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.homepage.update') }}">
        @csrf @method('PUT')
        @foreach($sections as $key => $section)
            <section class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ ucwords(str_replace('_', ' ', $key)) }}</h5>
                    <input type="hidden" name="sections[{{ $key }}][enabled]" value="0">
                    <label class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" name="sections[{{ $key }}][enabled]" value="1" @checked(old("sections.$key.enabled", $section['enabled']))><span class="form-check-label">Enabled</span></label>
                </div>
                <div class="card-body row g-3">
                    @if($key !== 'hero')<div class="col-md-6"><label class="form-label">Section title</label><input class="form-control" name="sections[{{ $key }}][title]" value="{{ old("sections.$key.title", $section['title']) }}"></div>@endif
                    @if($key !== 'hero' && $key !== 'promotion')<div class="col-md-3"><label class="form-label">Item limit</label><input class="form-control" type="number" min="1" max="50" name="sections[{{ $key }}][item_limit]" value="{{ old("sections.$key.item_limit", $section['item_limit']) }}"></div>@endif
                    <div class="col-md-3"><label class="form-label">Display order</label><input class="form-control" type="number" min="0" name="sections[{{ $key }}][display_order]" value="{{ old("sections.$key.display_order", $section['display_order']) }}"></div>
                    @if($key === 'hero')
                        <div class="col-md-6"><label class="form-label">Heading</label><input required maxlength="255" class="form-control" name="sections[hero][config][heading]" value="{{ old('sections.hero.config.heading', $section['config']['heading'] ?? $section['title']) }}"></div>
                        <div class="col-md-6"><label class="form-label">Subheading</label><input class="form-control" name="sections[hero][config][subheading]" value="{{ old('sections.hero.config.subheading', $section['config']['subheading'] ?? '') }}"></div>
                        <div class="col-md-4"><label class="form-label">Hero image (Media Library)</label><select class="form-select" name="sections[hero][config][media_id]"><option value="">No image</option>@foreach($media as $m)<option value="{{ $m->id }}" @selected((string)old('sections.hero.config.media_id', $section['config']['media_id'] ?? '') === (string)$m->id)>{{ $m->original_name }} (#{{ $m->id }})</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">CTA text</label><input class="form-control" name="sections[hero][config][cta_text]" value="{{ old('sections.hero.config.cta_text', $section['config']['cta_text'] ?? '') }}"></div>
                        <div class="col-md-4"><label class="form-label">CTA URL</label><input class="form-control" name="sections[hero][config][cta_url]" value="{{ old('sections.hero.config.cta_url', $section['config']['cta_url'] ?? '') }}"></div>
                    @elseif(in_array($key, ['featured_cars','popular_brands','car_categories']))
                        @php($ids = $section['config']['item_ids'] ?? [])
                        @php($type = $key === 'featured_cars' ? 'cars' : ($key === 'popular_brands' ? 'brands' : 'categories'))
                        @php($options = $type === 'cars' ? $cars : ($type === 'brands' ? $brands : $categories))
                        <div class="col-12"><label class="form-label" for="homepage-{{ $key }}-trigger">Selected {{ $type }}</label>@include('admin.shared.multi-select', ['id'=>'homepage-'.$key,'name'=>"sections[$key][config][item_ids]",'options'=>$options->map(fn($o)=>['value'=>$o->id,'label'=>($o->name ?? '').(isset($o->brand) && $o->brand ? ' — '.$o->brand->name : '')]),'selected'=>old("sections.$key.config.item_ids",$ids),'placeholder'=>'Select '.$type.'...','searchPlaceholder'=>'Search '.$type.'...','asyncUrl'=>url('/api/admin/homepage/options/'.$type)])<small class="text-muted">Search results are loaded as needed. Inactive records are excluded from the public response.</small></div>
                    @elseif($key === 'promotion')
                        <div class="col-md-6"><label class="form-label">Description</label><textarea class="form-control" name="sections[promotion][config][description]">{{ old('sections.promotion.config.description', $section['config']['description'] ?? '') }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Image (Media Library)</label><select class="form-select" name="sections[promotion][config][media_id]"><option value="">No image</option>@foreach($media as $m)<option value="{{ $m->id }}" @selected((string)old('sections.promotion.config.media_id', $section['config']['media_id'] ?? '') === (string)$m->id)>{{ $m->original_name }} (#{{ $m->id }})</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">CTA text</label><input class="form-control" name="sections[promotion][config][cta_text]" value="{{ old('sections.promotion.config.cta_text', $section['config']['cta_text'] ?? '') }}"></div>
                        <div class="col-md-6"><label class="form-label">CTA URL</label><input class="form-control" name="sections[promotion][config][cta_url]" value="{{ old('sections.promotion.config.cta_url', $section['config']['cta_url'] ?? '') }}"></div>
                    @endif
                </div>
            </section>
        @endforeach
        <button class="btn btn-primary">Save Changes</button>
    </form>
</div>
@endsection
