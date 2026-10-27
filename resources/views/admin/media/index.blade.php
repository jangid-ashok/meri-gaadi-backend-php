@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
        <div><h1 class="h3 mb-1">Media Library</h1><p class="text-body-secondary mb-0">Upload and reuse images across your content.</p></div>
        @if(Auth::user()->hasPermission('media.upload'))<label class="btn btn-primary mb-0">Upload images<input id="media-file" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" hidden></label>@endif
    </div>
    <div id="media-alert" class="alert d-none" role="status"></div>
    <div class="card"><div class="card-body">
        <form method="GET" action="{{ route('admin.media.index') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-4"><label class="form-label" for="media-search">Search filename</label><input id="media-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Original or stored filename"></div>
            <div class="col-md-2"><label class="form-label" for="media-format">Format</label><select id="media-format" name="format" class="form-select"><option value="">All formats</option>@foreach(['jpg','jpeg','png','gif','webp','avif'] as $format)<option value="{{ $format }}" @selected(request('format') === $format)>{{ strtoupper($format) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label" for="media-date-from">Uploaded from</label><input type="date" id="media-date-from" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label" for="media-date-to">Uploaded to</label><input type="date" id="media-date-to" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill" type="submit">Search</button><a class="btn btn-outline-secondary" href="{{ route('admin.media.index') }}">Clear</a></div>
        </form>
        <div class="row g-3">
            @forelse($media as $item)
                <div class="col-6 col-md-4 col-lg-3 col-xl-2"><button type="button" class="card h-100 w-100 text-start media-details" data-id="{{ $item->id }}" style="cursor:pointer"><img src="{{ $item->thumbnail_url }}" alt="{{ $item->alt_text ?: $item->original_name }}" loading="lazy" onerror="this.style.visibility='hidden';this.alt='Image unavailable'" class="card-img-top" style="height:150px;object-fit:cover"><div class="card-body p-2"><div class="small text-truncate" title="{{ $item->original_name }}">{{ $item->original_name ?: $item->file_name }}</div><div class="small text-body-secondary">{{ strtoupper($item->extension ?: 'image') }} · {{ $item->width }}×{{ $item->height }}</div></div></button></div>
            @empty
                <div class="col-12 text-center text-body-secondary py-5"><h2 class="h5">No images found</h2><p class="mb-0">Try changing the search or filters, or upload an image.</p></div>
            @endforelse
        </div>
        @if($media->total() > 0)<div class="card-footer mt-4">@include('admin.shared.pagination', ['paginator' => $media])</div>@endif
    </div></div>
</main>
<div class="modal fade" id="media-detail" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5">Image details</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="media-detail-body"></div><div class="modal-footer"><button id="media-copy" class="btn btn-outline-primary">Copy URL</button><button id="media-delete" class="btn btn-outline-danger d-none">Delete</button></div></div></div></div>
<script>
(() => {
 const endpoint = @json(url('/api/admin/media')), mediaIndex = @json(route('admin.media.index')), csrf = @json(csrf_token()), maxBytes = @json(config('media.max_image_kb', 5120) * 1024);
 const canDelete = @json(Auth::user()->hasPermission('media.delete'));
 const canUpdate = @json(Auth::user()->hasPermission('media.update'));
 let current = null, modal;
 const alertBox = document.getElementById('media-alert');
 function alert(message, type='success') { alertBox.className=`alert alert-${type}`; alertBox.textContent=message; }
 function escapeHtml(value){return String(value||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
 async function details(id){const r=await fetch(`${endpoint}/${id}`,{headers:{Accept:'application/json'}});current=(await r.json()).data;document.getElementById('media-detail-body').innerHTML=`<div class="row g-4"><div class="col-md-6"><img class="img-fluid rounded" onerror="this.style.visibility='hidden';this.alt='Image unavailable'" src="${current.url}" alt="${escapeHtml(current.alt_text||current.original_name)}"></div><div class="col-md-6"><dl><dt>Original filename</dt><dd>${escapeHtml(current.original_name)}</dd><dt>Type and size</dt><dd>${current.mime_type} · ${(current.size/1024/1024).toFixed(2)} MB</dd><dt>Dimensions</dt><dd>${current.width} × ${current.height}</dd><dt>URL</dt><dd class="text-break"><a href="${current.url}" target="_blank" rel="noopener">${escapeHtml(current.url)}</a></dd><dt>Uploaded by</dt><dd>${escapeHtml(current.uploaded_by||'Unknown')}</dd><dt>Created</dt><dd>${new Date(current.created_at).toLocaleString()}</dd></dl><label class="form-label" for="media-alt">Alt text</label><input id="media-alt" class="form-control mb-2" maxlength="255" value="${escapeHtml(current.alt_text)}"><label class="form-label" for="media-title">Title</label><input id="media-title" class="form-control" maxlength="255" value="${escapeHtml(current.title)}"><button id="media-save" class="btn btn-primary mt-3">Save metadata</button><hr><label class="form-label" for="media-replace-file">Replace image</label><input id="media-replace-file" class="form-control" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif"><button id="media-replace-submit" class="btn btn-outline-primary mt-2">Replace image</button></div></div>`;
   document.getElementById('media-delete').classList.toggle('d-none',!canDelete);document.getElementById('media-save').classList.toggle('d-none',!canUpdate);document.getElementById('media-alt').disabled=!canUpdate;document.getElementById('media-title').disabled=!canUpdate;document.getElementById('media-replace-file').classList.toggle('d-none',!canUpdate);document.getElementById('media-replace-submit').classList.toggle('d-none',!canUpdate);document.getElementById('media-copy').onclick=()=>navigator.clipboard.writeText(current.url).then(()=>alert('URL copied.')).catch(()=>alert('Could not copy URL.','danger'));
   document.getElementById('media-save').onclick=async()=>{const response=await fetch(`${endpoint}/${id}`,{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json'},body:JSON.stringify({alt_text:document.getElementById('media-alt').value,title:document.getElementById('media-title').value})});if(response.ok){window.location.reload();}else alert('Could not save metadata.','danger');};
   document.getElementById('media-replace-submit').onclick=async()=>{const file=document.getElementById('media-replace-file').files[0];if(!file){alert('Choose a replacement image first.','warning');return;}const data=new FormData();data.append('file',file);const response=await fetch(`${endpoint}/${id}/replace`,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json'},body:data});if(response.ok){window.location.reload();}else{const payload=await response.json();alert(payload.message||'Replacement failed.','danger');}};
   document.getElementById('media-delete').onclick=async()=>{if(!await confirmAdminAction('Delete this image?'))return;const response=await fetch(`${endpoint}/${id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json'}});if(response.ok){window.location.href=mediaIndex;}else{const payload=await response.json();const refs=(payload.references||[]).join(', ');alert(payload.message+(refs?` References: ${refs}`:''),'danger');}};
   modal=bootstrap.Modal.getOrCreateInstance(document.getElementById('media-detail'));modal.show();
 }
 document.querySelectorAll('.media-details').forEach(button=>button.addEventListener('click',()=>details(button.dataset.id)));
 const picker=document.getElementById('media-file');if(picker)picker.onchange=async()=>{const file=picker.files[0];if(!file)return;if(file.size>maxBytes){alert(`Image must be ${(maxBytes/1024/1024).toFixed(1)} MB or smaller.`,'danger');picker.value='';return;}const data=new FormData();data.append('file',file);alertBox.className='alert alert-info';alertBox.textContent='Uploading…';const r=await fetch(endpoint,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json'},body:data});if(r.ok){window.location.href=mediaIndex;}else{const body=await r.json();alert(body.message||'Upload failed. Check that the file is a supported image.','danger');}picker.value='';};
})();
</script>
@endsection
