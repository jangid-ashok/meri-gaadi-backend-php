@php
    $fieldId = $id ?? 'multi-select-'.\Illuminate\Support\Str::random(8);
    $selectedValues = array_map('strval', old($name, $selected ?? []));
    $optionRows = collect($options ?? [])->map(fn($option) => [
        'value' => (string) (is_array($option) ? $option['value'] : $option->id),
        'label' => is_array($option) ? $option['label'] : $option->name,
        'description' => is_array($option) ? ($option['description'] ?? '') : '',
        'image' => is_array($option) ? ($option['image'] ?? '') : '',
    ])->values();
    $componentError = $errorMessage ?? $errors->first($name);
    if (!$componentError) {
        foreach ($errors->getMessages() as $errorKey => $messages) {
            if (str_starts_with($errorKey, $name.'.')) { $componentError = $messages[0] ?? ''; break; }
        }
    }
@endphp
<div class="mg-multiselect" id="{{ $fieldId }}" data-name="{{ $name }}" data-options="{{ $optionRows->toJson() }}" data-value="{{ json_encode($selectedValues) }}" data-placeholder="{{ $placeholder ?? 'Select items...' }}" data-async-url="{{ $asyncUrl ?? '' }}" data-error="{{ $componentError }}" data-disabled="{{ !empty($disabled) ? '1' : '0' }}">
    <button type="button" id="{{ $fieldId }}-trigger" class="form-control text-start d-flex align-items-center justify-content-between mg-multiselect-trigger" aria-label="{{ $ariaLabel ?? $placeholder ?? 'Select items' }}" aria-invalid="{{ $componentError ? 'true' : 'false' }}" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $fieldId }}-popup" @disabled(!empty($disabled))>
        <span class="mg-multiselect-chips"></span><span class="text-secondary ms-2">▾</span>
    </button>
    <div class="mg-multiselect-popup shadow" id="{{ $fieldId }}-popup" hidden>
        <input type="search" class="form-control form-control-sm mb-2 mg-multiselect-search" placeholder="{{ $searchPlaceholder ?? 'Search...' }}" aria-label="{{ $searchPlaceholder ?? 'Search options' }}">
        <div class="mg-multiselect-list" role="listbox" aria-multiselectable="true" tabindex="-1"></div>
        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 small">
            <span class="mg-multiselect-count text-secondary" aria-live="polite"></span>
            <span class="d-flex gap-3"><button type="button" class="btn btn-link btn-sm p-0 mg-multiselect-all">Select shown</button><button type="button" class="btn btn-link btn-sm p-0 mg-multiselect-clear">Clear all</button></span>
        </div>
        <div class="mg-multiselect-message small text-secondary mt-2" role="status"></div>
    </div>
    <div class="mg-multiselect-inputs"></div>
    @if($componentError)<div class="invalid-feedback d-block">{{ $componentError }}</div>@endif
</div>
<style>
.mg-multiselect{position:relative}.mg-multiselect-trigger{min-height:42px;height:auto;flex-wrap:wrap;gap:.25rem}.mg-multiselect-chips{display:flex;flex-wrap:wrap;gap:.3rem}.mg-multiselect-chip{display:inline-flex;align-items:center;gap:.3rem;background:#e9eefb;color:#253b69;border-radius:1rem;padding:.2rem .5rem;font-size:.8rem}.mg-multiselect-chip button{border:0;background:none;color:inherit;padding:0;line-height:1}.mg-multiselect-popup{position:absolute;z-index:1050;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #d9dee8;border-radius:.5rem;padding:.6rem;max-height:340px;display:flex;flex-direction:column}.mg-multiselect-popup[hidden]{display:none}.mg-multiselect-list{overflow:auto;min-height:55px}.mg-multiselect-option{display:flex;gap:.55rem;align-items:center;padding:.45rem;border-radius:.35rem;cursor:pointer}.mg-multiselect-option:hover,.mg-multiselect-option:focus{background:#f1f4fa;outline:0}.mg-multiselect-option img{width:30px;height:30px;object-fit:cover;border-radius:.25rem}.mg-multiselect.is-invalid .mg-multiselect-trigger{border-color:#dc3545}
</style>
<script>
(() => {
    const root=document.getElementById(@json($fieldId)); if(!root||root.dataset.ready)return; root.dataset.ready='1';
    const name=root.dataset.name, trigger=root.querySelector('.mg-multiselect-trigger'), popup=root.querySelector('.mg-multiselect-popup'), search=root.querySelector('.mg-multiselect-search'), list=root.querySelector('.mg-multiselect-list'), chips=root.querySelector('.mg-multiselect-chips'), inputs=root.querySelector('.mg-multiselect-inputs'), message=root.querySelector('.mg-multiselect-message'), count=root.querySelector('.mg-multiselect-count'), selectShown=root.querySelector('.mg-multiselect-all');
    let options=JSON.parse(root.dataset.options||'[]'), selected=JSON.parse(root.dataset.value||'[]').map(String), active=-1, timer=null, requestSequence=0;
    const labels=new Map(options.map(o=>[String(o.value),o]));
    if(root.dataset.error)root.classList.add('is-invalid');
    const safe=(s)=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const matching=()=>{const term=search.value.trim().toLowerCase();return options.filter(o=>!term||o.label.toLowerCase().includes(term)||(o.description||'').toLowerCase().includes(term));};
    function sync(){chips.innerHTML='';const visible=selected.slice(0,3);visible.forEach(v=>{const o=labels.get(v)||options.find(x=>String(x.value)===v);const chip=document.createElement('span');chip.className='mg-multiselect-chip';chip.innerHTML=`${safe(o?.label||`#${v}`)} <button type="button" aria-label="Remove ${safe(o?.label||v)}" ${root.dataset.disabled==='1'?'disabled':''}>×</button>`;chip.querySelector('button').onclick=e=>{e.stopPropagation();selected=selected.filter(x=>x!==v);render();};chips.appendChild(chip);});if(selected.length>3){const more=document.createElement('span');more.className='mg-multiselect-chip';more.textContent=`+${selected.length-3} more`;chips.appendChild(more);}if(!selected.length){const placeholder=document.createElement('span');placeholder.className='text-secondary';placeholder.textContent=root.dataset.placeholder;chips.appendChild(placeholder);}inputs.innerHTML=selected.map(v=>`<input type="hidden" name="${safe(name)}[]" value="${safe(v)}" ${root.dataset.disabled==='1'?'disabled':''}>`).join('');count.textContent=`${selected.length} selected`;}
    function render(){const rows=matching();list.innerHTML='';rows.forEach((o,i)=>{const row=document.createElement('div');row.className='mg-multiselect-option';row.setAttribute('role','option');row.setAttribute('aria-selected',selected.includes(String(o.value))?'true':'false');row.tabIndex=i===active?0:-1;row.innerHTML=`<input type="checkbox" tabindex="-1" ${selected.includes(String(o.value))?'checked':''}><span>${o.image?`<img src="${safe(o.image)}" alt="">`:''}</span><span><span class="d-block">${safe(o.label)}</span>${o.description?`<small class="text-secondary">${safe(o.description)}</small>`:''}</span>`;row.onclick=()=>toggle(String(o.value));row.onkeydown=e=>{if(e.key==='Escape'){popup.hidden=true;trigger.setAttribute('aria-expanded','false');trigger.focus();}else if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle(String(o.value));}else if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();active=(i+(e.key==='ArrowDown'?1:rows.length-1))%rows.length;render();list.children[active]?.focus();}};list.appendChild(row);});message.textContent=rows.length?'':(search.value?'No results found.':'No options available.');selectShown.hidden=!!root.dataset.asyncUrl;sync();}
    function toggle(v){selected=selected.includes(v)?selected.filter(x=>x!==v):[...selected,v];render();}
    async function load(){if(!root.dataset.asyncUrl)return;const sequence=++requestSequence;message.textContent='Loading…';try{const url=new URL(root.dataset.asyncUrl,location.origin);url.searchParams.set('search',search.value);selected.forEach(v=>url.searchParams.append('selected[]',v));const response=await fetch(url,{headers:{Accept:'application/json'}});if(sequence!==requestSequence)return;if(!response.ok)throw new Error();const payload=await response.json();if(sequence!==requestSequence)return;const data=payload.data?.data??payload.data??[];options=data.map(o=>({value:String(o.value??o.id),label:o.label??o.name,description:o.description??'',image:o.image??''}));options.forEach(o=>labels.set(String(o.value),o));render();}catch{if(sequence===requestSequence)message.textContent='Could not load options. Try again.';}}
    trigger.onclick=()=>{if(root.dataset.disabled==='1')return;const opening=popup.hidden;popup.hidden=!opening;trigger.setAttribute('aria-expanded',String(opening));if(opening){search.focus();if(root.dataset.asyncUrl)load();}};
    search.oninput=()=>{if(root.dataset.asyncUrl){requestSequence++;clearTimeout(timer);timer=setTimeout(load,250);}else render();};
    search.onkeydown=e=>{if(e.key==='Escape'){popup.hidden=true;trigger.setAttribute('aria-expanded','false');trigger.focus();}else if(e.key==='ArrowDown'){e.preventDefault();active=0;render();list.children[0]?.focus();}};
    trigger.onkeydown=e=>{if(e.key==='Escape'){popup.hidden=true;trigger.setAttribute('aria-expanded','false');}else if(e.key==='Backspace'&&!search.value&&selected.length){selected.pop();render();}};
    selectShown.onclick=()=>{matching().forEach(o=>{const v=String(o.value);if(!selected.includes(v))selected.push(v);});render();};
    root.querySelector('.mg-multiselect-clear').onclick=()=>{selected=[];render();};
    document.addEventListener('click',e=>{if(!root.contains(e.target)){popup.hidden=true;trigger.setAttribute('aria-expanded','false');}});
    render();
})();
</script>
