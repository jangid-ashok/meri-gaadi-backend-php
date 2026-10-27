@extends('admin.layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y"><h4 class="fw-bold py-3">{{ $page_title }}</h4>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $seo->exists ? route('admin.seo.update',$seo) : route('admin.seo.store') }}">
        @csrf @if($seo->exists)@method('PUT')@endif
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">SEO target</h5></div><div class="card-body row g-3">
            @if($seo->exists)<div class="col-md-6"><label class="form-label">Entity</label><div class="form-control bg-light">{{ $entityType === 'homepage' ? 'Homepage' : ($entity?->name ?? $entity?->title ?? 'Unavailable entity') }} · {{ ucfirst($entityType ?? 'unknown') }}</div></div>
            @else
                <div class="col-md-4"><label class="form-label" for="entity_type">Entity type *</label><select class="form-select" name="entity_type" id="entity_type" required><option value="">Choose type</option>@foreach($types as $type)<option value="{{ $type }}" @selected(old('entity_type')===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
                <div class="col-md-8" id="entity-selector" hidden><label class="form-label" for="entity-search">Entity *</label><input id="entity_id" name="entity_id" type="hidden" value="{{ old('entity_id') }}"><input class="form-control" id="entity-search" type="search" placeholder="Search by name or slug" autocomplete="off"><div id="entity-results" class="list-group mt-1" role="listbox"></div><small id="entity-selected" class="text-success"></small></div>
                <div class="col-12" id="homepage-help" hidden><small class="text-muted">Homepage SEO is managed as a single record.</small></div>
            @endif
        </div></div>
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Basic SEO</h5></div><div class="card-body row g-3">
            <div class="col-12"><label class="form-label" for="meta_title">Meta title *</label><input class="form-control @error('meta_title') is-invalid @enderror" id="meta_title" name="meta_title" maxlength="255" required value="{{ old('meta_title',$seo->meta_title) }}"><div class="form-text"><span id="title-count">0</span> characters · keep concise</div>@error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="meta_description">Meta description</label><textarea class="form-control" id="meta_description" name="meta_description" maxlength="2000" rows="3">{{ old('meta_description',$seo->meta_description) }}</textarea><div class="form-text"><span id="description-count">0</span> characters · concise descriptions are easier to scan</div></div>
            <div class="col-md-8"><label class="form-label" for="canonical_url">Canonical URL</label><input class="form-control" type="url" id="canonical_url" name="canonical_url" maxlength="2048" value="{{ old('canonical_url',$seo->canonical_url) }}" placeholder="https://example.com/page"></div>
            <div class="col-md-4"><label class="form-label" for="robots">Robots</label><select class="form-select" id="robots" name="robots">@foreach($robots as $robot)<option value="{{ $robot }}" @selected(old('robots',$seo->robots)===$robot)>{{ $robot }}</option>@endforeach</select></div>
        </div></div>
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Social sharing</h5></div><div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="og_title">Open Graph title</label><input class="form-control" id="og_title" name="og_title" maxlength="255" value="{{ old('og_title',$seo->og_title) }}"></div>
            <div class="col-md-6"><label class="form-label" for="og_description">Open Graph description</label><textarea class="form-control" id="og_description" name="og_description" rows="2" maxlength="2000">{{ old('og_description',$seo->og_description) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Open Graph image</label>@if($seo->ogImage)<img class="d-block rounded mb-2" src="{{ $seo->ogImage->url }}" alt="Current OG image" style="max-width:220px;max-height:130px;object-fit:cover">@endif @if(Auth::user()->hasPermission('media.view'))@include('admin.shared.media-picker',['field'=>'og_image_id','value'=>$seo->og_image_id])@else<input type="hidden" name="og_image_id" value="{{ $seo->og_image_id }}"><small class="text-muted">Media Library access is required to replace this image.</small>@endif</div>
            <div class="col-md-3"><label class="form-label" for="twitter_card_type">X/Twitter card type</label><select class="form-select" id="twitter_card_type" name="twitter_card_type">@foreach($cards as $card)<option value="{{ $card }}" @selected(old('twitter_card_type',$seo->twitter_card_type)===$card)>{{ $card }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="twitter_title">X/Twitter title</label><input class="form-control" id="twitter_title" name="twitter_title" maxlength="255" value="{{ old('twitter_title',$seo->twitter_title) }}"></div>
            <div class="col-md-6"><label class="form-label" for="twitter_description">X/Twitter description</label><textarea class="form-control" id="twitter_description" name="twitter_description" rows="2" maxlength="2000">{{ old('twitter_description',$seo->twitter_description) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">X/Twitter image</label>@if($seo->twitterImage)<img class="d-block rounded mb-2" src="{{ $seo->twitterImage->url }}" alt="Current X/Twitter image" style="max-width:220px;max-height:130px;object-fit:cover">@endif @if(Auth::user()->hasPermission('media.view'))@include('admin.shared.media-picker',['field'=>'twitter_image_id','value'=>$seo->twitter_image_id])@else<input type="hidden" name="twitter_image_id" value="{{ $seo->twitter_image_id }}"><small class="text-muted">Media Library access is required to replace this image.</small>@endif</div>
        </div></div>
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Search preview</h5></div><div class="card-body"><div class="border rounded p-3" style="max-width:680px"><div id="preview-title" class="fw-semibold text-primary"></div><div id="preview-url" class="small text-success text-truncate"></div><div id="preview-description" class="small text-secondary"></div></div><small class="text-muted d-block mt-2">Visual preview only; actual search display may vary.</small></div></div>
        <div class="d-flex gap-2 mb-5"><button class="btn btn-primary">Save SEO metadata</button><a class="btn btn-outline-secondary" href="{{ route('admin.seo.index') }}">Cancel</a></div>
    </form>
</div>
<script>
(() => {
    const title=document.getElementById('meta_title'),description=document.getElementById('meta_description'),canonical=document.getElementById('canonical_url');
    const update=()=>{document.getElementById('title-count').textContent=title.value.length;document.getElementById('description-count').textContent=description.value.length;document.getElementById('preview-title').textContent=title.value||'Page title';document.getElementById('preview-url').textContent=canonical.value||'Canonical URL will be generated from the selected page';document.getElementById('preview-description').textContent=description.value||'A factual description will be generated from the selected page.';};
    [title,description,canonical].forEach(input=>input.addEventListener('input',update));update();
    const type=document.getElementById('entity_type');if(!type)return;
    const selector=document.getElementById('entity-selector'),home=document.getElementById('homepage-help'),search=document.getElementById('entity-search'),results=document.getElementById('entity-results'),hidden=document.getElementById('entity_id'),selected=document.getElementById('entity-selected');let timer,requestSequence=0;
    async function load(){
        if(!['car','brand','category','article'].includes(type.value))return;
        const sequence=++requestSequence;
        selector.hidden=false;home.hidden=true;
        const params=new URLSearchParams({search:search.value});if(hidden.value)params.set('selected_id',hidden.value);
        results.innerHTML='<div class="list-group-item text-secondary">Loading…</div>';
        let response;
        try{response=await fetch(`/api/admin/seo/entities/${type.value}?${params}`,{headers:{Accept:'application/json'}});}catch{if(sequence===requestSequence)results.innerHTML='<div class="list-group-item text-danger">Unable to load results. Please try again.</div>';return;}
        if(sequence!==requestSequence)return;
        if(!response.ok){results.innerHTML='<div class="list-group-item text-danger">Unable to load results.</div>';return;}
        let data;
        try{data=(await response.json()).data||[];}catch{if(sequence===requestSequence)results.innerHTML='<div class="list-group-item text-danger">Unable to load results. Please try again.</div>';return;}
        if(sequence!==requestSequence)return;
        if(hidden.value){const current=data.find(item=>String(item.id)===String(hidden.value));if(current){search.value=current.label;selected.textContent=`Selected: ${current.label}`;results.innerHTML='';return;}}
        const escape=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        results.innerHTML=data.length?data.map(item=>`<button class="list-group-item list-group-item-action" type="button" data-id="${item.id}" data-label="${escape(item.label)}"><strong>${escape(item.label)}</strong><small class="d-block text-secondary">${escape(item.description)}</small></button>`).join(''):'<div class="list-group-item text-secondary">No results found.</div>';
        results.querySelectorAll('[data-id]').forEach(button=>button.onclick=()=>{hidden.value=button.dataset.id;search.value=button.dataset.label;selected.textContent=`Selected: ${button.dataset.label}`;results.innerHTML='';});
    }
    type.onchange=()=>{hidden.value='';search.value='';selected.textContent='';if(type.value==='homepage'){selector.hidden=true;home.hidden=false;}else if(type.value){search.placeholder=`Search ${type.value}s by name or slug`;load();}else{selector.hidden=true;home.hidden=true;}};
    search.oninput=()=>{hidden.value='';selected.textContent='';requestSequence++;clearTimeout(timer);timer=setTimeout(load,250);};
    if(type.value){if(type.value==='homepage'){selector.hidden=true;home.hidden=false;}else{load();}}
})();
</script>
@endsection
