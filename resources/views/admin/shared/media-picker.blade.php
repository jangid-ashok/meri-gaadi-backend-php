<div class="col-12 mt-2">
    <input type="hidden" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, $value ?? '') }}">
    <button type="button" class="btn btn-sm btn-outline-primary" id="{{ $field }}-open">Choose from Media Library</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="{{ $field }}-clear">Clear selection</button>
    <div class="small text-body-secondary mt-1">Choosing an existing image overrides the file upload.</div>
</div>
<div class="modal fade" id="{{ $field }}-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5">Choose an image</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <div class="row g-2 mb-3"><div class="col"><input class="form-control" id="{{ $field }}-search" placeholder="Search filename" aria-label="Search media"></div><div class="col-auto"><button class="btn btn-primary" type="button" id="{{ $field }}-search-button">Search</button></div>@if(Auth::user()->hasPermission('media.upload'))<div class="col-auto"><label class="btn btn-outline-primary mb-0">Upload<input type="file" id="{{ $field }}-upload" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" hidden></label></div>@endif</div>
            <div class="row g-3" id="{{ $field }}-grid"></div>
        </div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" id="{{ $field }}-previous">Previous</button><span class="small text-body-secondary" id="{{ $field }}-page"></span><button class="btn btn-outline-secondary" type="button" id="{{ $field }}-next">Next</button></div>
    </div></div>
</div>
<script>
(() => {
    const field = @json($field), endpoint = @json(url('/api/admin/media')), csrf = @json(csrf_token()), maxBytes = @json(config('media.max_image_kb', 5120) * 1024);
    const hidden = document.getElementById(field), modalElement = document.getElementById(field+'-modal'), grid = document.getElementById(field+'-grid');
    let modal = null;
    let page = 1, next = false, previous = false;
    async function load(){
        const params = new URLSearchParams({disk:'media', selectable:'1', page, search:document.getElementById(field+'-search').value});
        const response = await fetch(`${endpoint}?${params}`, {headers:{Accept:'application/json'}}); if(!response.ok)return;
        const paginator = (await response.json()).data; next=!!paginator.next_page_url; previous=!!paginator.prev_page_url;
        document.getElementById(field+'-page').textContent=`${paginator.from||0}–${paginator.to||0} of ${paginator.total}`;
        document.getElementById(field+'-previous').disabled=!previous; document.getElementById(field+'-next').disabled=!next;
        grid.innerHTML=paginator.data.length?paginator.data.map(item=>`<div class="col-6 col-md-3 col-lg-2"><button type="button" class="card h-100 w-100 text-start" data-media-id="${item.id}" data-url="${item.url}"><img src="${item.thumbnail_url}" alt="" loading="lazy" onerror="this.style.visibility='hidden';this.alt='Image unavailable'" style="height:125px;object-fit:cover" class="card-img-top"><div class="card-body p-2"><span class="small text-truncate d-block">${escapeHtml(item.original_name)}</span></div></button></div>`).join(''):'<div class="col-12 text-center text-body-secondary py-5">No images found for this storage location.</div>';
        grid.querySelectorAll('[data-media-id]').forEach(button=>button.onclick=()=>{hidden.value=button.dataset.mediaId;const file=document.querySelector(`[name="${field.replace('_media_id','')}"]`);if(file&&file.type==='file')file.value='';let preview=document.getElementById(field+'-preview');if(!preview){preview=document.createElement('img');preview.id=field+'-preview';preview.className='mt-2 rounded border';preview.style='max-width:220px;max-height:150px;object-fit:contain';document.getElementById(field).parentElement.appendChild(preview);}preview.src=button.dataset.url;preview.alt='Selected image preview';modal?.hide();});
    }
    function escapeHtml(value){return String(value||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
    document.getElementById(field+'-open').onclick=()=>{modal=bootstrap.Modal.getOrCreateInstance(modalElement);modal.show();load();};
    document.getElementById(field+'-search-button').onclick=()=>{page=1;load();};
    document.getElementById(field+'-search').onkeydown=e=>{if(e.key==='Enter'){page=1;load();}};
    document.getElementById(field+'-previous').onclick=()=>{if(previous){page--;load();}}; document.getElementById(field+'-next').onclick=()=>{if(next){page++;load();}};
    const uploadInput=document.querySelector(`[name="${field.replace('_media_id','')}"]`);
    function previewImage(url){let preview=document.getElementById(field+'-preview');if(!preview){preview=document.createElement('img');preview.id=field+'-preview';preview.className='mt-2 rounded border';preview.style='max-width:220px;max-height:150px;object-fit:contain';hidden.parentElement.appendChild(preview);}preview.src=url;preview.alt='Selected image preview';}
    if(uploadInput)uploadInput.addEventListener('change',()=>{if(uploadInput.files&&uploadInput.files[0]){hidden.value='';previewImage(URL.createObjectURL(uploadInput.files[0]));}});
    document.getElementById(field+'-clear').onclick=()=>{hidden.value='';if(uploadInput)uploadInput.value='';const preview=document.getElementById(field+'-preview');if(preview)preview.remove();};
    const pickerUpload=document.getElementById(field+'-upload');
    if(pickerUpload)pickerUpload.onchange=async e=>{const file=e.target.files[0];if(!file)return;if(file.size>maxBytes){alert(`Image must be ${(maxBytes/1024/1024).toFixed(1)} MB or smaller.`);e.target.value='';return;}const data=new FormData();data.append('file',file);const response=await fetch(endpoint,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json'},body:data});if(response.ok){page=1;await load();}else{const payload=await response.json();alert(payload.message||'Image upload failed.');}e.target.value='';};
})();
</script>
