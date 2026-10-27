<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Brand, CarModel, Category, Media, SeoMeta};
use App\Models\Admin\Blogs as Article;
use App\Services\SeoMetadataService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SeoController extends Controller
{
    private const TYPES = ['homepage','car','brand','category','article'];

    public function index(Request $request)
    {
        $request->validate(['search'=>['nullable','string','max:255'],'entity_type'=>['nullable',Rule::in(self::TYPES)],'robots'=>['nullable',Rule::in(SeoMeta::ROBOTS)]]);
        $query = SeoMeta::with(['ogImage','twitterImage'])
            ->when($request->filled('entity_type'),fn($q)=>$q->where('seoable_type',$this->storedType($request->input('entity_type'))))
            ->when($request->filled('robots'),fn($q)=>$q->where('robots',$request->input('robots')))
            ->when($request->filled('search'),function($q) use ($request) {
                $term='%'.addcslashes($request->input('search'),'%_').'%';
                $search=$request->input('search');
                $q->where(function($inner) use ($term,$search) {
                    $inner->where('meta_title','like',$term)->orWhere('meta_description','like',$term)
                        ->orWhereHasMorph('seoable',[CarModel::class,Brand::class,Category::class,Article::class],fn($target,$type)=>$target->where($type===Article::class?'title':'name','like',$term));
                    if(stripos('homepage',$search)!==false) $inner->orWhere('seoable_type','homepage');
                });
            })->latest('updated_at');
        $items=$this->paginateRequest($request,$query);
        $labels=$this->entityLabels($items->getCollection());
        if($request->expectsJson()) {
            $items->getCollection()->transform(fn($item)=>$this->resource($item,$labels));
            return response()->json(['data'=>$items]);
        }
        foreach($items as $item) {
            $item->entity_type=$this->publicType($item->seoable_type);
            $item->entity_label=$item->seoable_type==='homepage'?'Homepage':($labels[$item->seoable_type.'|'.$item->seoable_id] ?? 'Unavailable entity');
        }
        return view('admin.seo.index',['items'=>$items,'filters'=>$request->only('search','entity_type','robots'),'types'=>self::TYPES,'robots'=>SeoMeta::ROBOTS,'page_title'=>'SEO Management','leftMenuActive'=>'seo']);
    }

    public function create()
    {
        return view('admin.seo.form',['seo'=>new SeoMeta(['robots'=>'index, follow','twitter_card_type'=>'summary_large_image']),'entity'=>null,'entityType'=>null,'types'=>self::TYPES,'robots'=>SeoMeta::ROBOTS,'cards'=>SeoMeta::TWITTER_CARDS,'page_title'=>'Create SEO Metadata','leftMenuActive'=>'seo']);
    }

    public function store(Request $request)
    {
        $data=$this->validated($request);
        $type=$data['entity_type'];
        $targetId=$type==='homepage' ? 1 : (int)$data['entity_id'];
        $storedType=$this->storedType($type);
        if(SeoMeta::where('seoable_type',$storedType)->where('seoable_id',$targetId)->exists()) {
            throw ValidationException::withMessages(['entity_id'=>'SEO metadata already exists for this entity.']);
        }
        $this->authorizeMedia($request,$data);
        unset($data['entity_type'],$data['entity_id']);
        $seo=SeoMeta::create($data+['seoable_type'=>$storedType,'seoable_id'=>$targetId])->load(['ogImage','twitterImage']);
        if($request->expectsJson()) return response()->json(['data'=>$this->resource($seo)],201);
        return redirect()->route('admin.seo.index')->with('status','SEO metadata created.');
    }

    public function show(SeoMeta $seo)
    {
        return response()->json(['data'=>$this->resource($seo->load(['ogImage','twitterImage']))]);
    }

    public function edit(SeoMeta $seo)
    {
        $seo->load(['ogImage','twitterImage']);
        return view('admin.seo.form',['seo'=>$seo,'entity'=>$this->targetEntity($seo),'entityType'=>$this->publicType($seo->seoable_type),'types'=>self::TYPES,'robots'=>SeoMeta::ROBOTS,'cards'=>SeoMeta::TWITTER_CARDS,'page_title'=>'Edit SEO Metadata','leftMenuActive'=>'seo']);
    }

    public function update(Request $request, SeoMeta $seo)
    {
        $data=$this->validated($request,false);
        $this->authorizeMedia($request,$data,$seo);
        $seo->update($data);
        $seo->load(['ogImage','twitterImage']);
        if($request->expectsJson()) return response()->json(['data'=>$this->resource($seo)]);
        return redirect()->route('admin.seo.index')->with('status','SEO metadata updated.');
    }

    public function destroy(Request $request, SeoMeta $seo)
    {
        $seo->delete();
        return $request->expectsJson() ? response()->noContent() : redirect()->route('admin.seo.index')->with('status','SEO metadata deleted.');
    }

    public function publicHomepage(SeoMetadataService $service)
    {
        $seo=SeoMeta::with(['ogImage','twitterImage'])->where('seoable_type','homepage')->where('seoable_id',1)->first();
        return response()->json(['data'=>['entity_type'=>'homepage','seo'=>$service->metadata('homepage',null,$seo)]]);
    }

    public function publicCar(string $brandSlug, string $carSlug, SeoMetadataService $service)
    {
        $car = CarModel::active()->where('slug', $carSlug)
            ->whereHas('brand', fn ($query) => $query->active()->where('slug', $brandSlug))
            ->with('brand:id,name,slug')->firstOrFail();

        return response()->json(['data' => [
            'entity_type' => 'car', 'brand_slug' => $brandSlug, 'slug' => $carSlug,
            'seo' => $service->metadata('car', $car),
        ]]);
    }

    public function publicEntity(string $type, string $slug, SeoMetadataService $service)
    {
        abort_unless(in_array($type,['car','brand','category','article'],true),404);
        $metadata=$service->publicSeoForSlug($type,$slug);
        abort_if($metadata===null,404);
        return response()->json(['data'=>['entity_type'=>$type,'slug'=>$slug,'seo'=>$metadata]]);
    }

    public function entities(Request $request, string $type)
    {
        abort_unless(in_array($type,['car','brand','category','article'],true),404);
        $request->validate(['search'=>['nullable','string','max:100'],'selected_id'=>['nullable','integer']]);
        $term=trim((string)$request->input('search',''));
        $model=$this->modelFor($type);
        $query=match($type) {
            'car'=>CarModel::active()->with('brand:id,name')->when($term,fn($q)=>$q->where(fn($inner)=>$inner->where('name','like','%'.addcslashes($term,'%_').'%')->orWhere('slug','like','%'.addcslashes($term,'%_').'%'))),
            'brand'=>Brand::active()->when($term,fn($q)=>$q->where(fn($inner)=>$inner->where('name','like','%'.addcslashes($term,'%_').'%')->orWhere('slug','like','%'.addcslashes($term,'%_').'%'))),
            'category'=>Category::active()->when($term,fn($q)=>$q->where(fn($inner)=>$inner->where('name','like','%'.addcslashes($term,'%_').'%')->orWhere('slug','like','%'.addcslashes($term,'%_').'%'))),
            default=>Article::where('is_deleted','no')->where('status','active')->where('publication_status','published')->whereNotNull('published_at')->where('published_at','<=',now())->when($term,fn($q)=>$q->where(fn($inner)=>$inner->where('title','like','%'.addcslashes($term,'%_').'%')->orWhere('slug','like','%'.addcslashes($term,'%_').'%'))),
        };
        $items=$query->orderBy($type==='article'?'title':'name')->limit(25)->get();
        if($request->filled('selected_id')) $items=$items->merge($model::whereKey($request->integer('selected_id'))->get())->unique('id')->values();
        return response()->json(['data'=>$items->map(fn($item)=>['id'=>$item->id,'label'=>$item->name ?? $item->title,'description'=>$item->slug.($type==='car' && $item->brand ? ' · '.$item->brand->name : '')])]);
    }

    private function validated(Request $request, bool $creating=true): array
    {
        $rules=[
            'meta_title'=>['required','string','max:255','not_regex:/^\s*$/'],
            'meta_description'=>['nullable','string','max:2000'],
            'canonical_url'=>['nullable','string','max:2048','regex:/^https?:\/\/[^\s<>"\']+$/i'],
            'og_title'=>['nullable','string','max:255'],'og_description'=>['nullable','string','max:2000'],
            'og_image_id'=>['nullable','integer',Rule::exists('media','id')->where(fn($q)=>$q->where('disk','media')->where('mime','like','image/%')->whereNull('blog_id')->whereNotNull('created_by'))],
            'robots'=>['required',Rule::in(SeoMeta::ROBOTS)],'twitter_card_type'=>['required',Rule::in(SeoMeta::TWITTER_CARDS)],
            'twitter_title'=>['nullable','string','max:255'],'twitter_description'=>['nullable','string','max:2000'],
            'twitter_image_id'=>['nullable','integer',Rule::exists('media','id')->where(fn($q)=>$q->where('disk','media')->where('mime','like','image/%')->whereNull('blog_id')->whereNotNull('created_by'))],
        ];
        if($creating) {
            $rules['entity_type']=['required',Rule::in(self::TYPES)];
            $rules['entity_id']=['required_unless:entity_type,homepage','nullable','integer'];
        }
        $data=$request->validate($rules);
        if($creating && $data['entity_type']!=='homepage') {
            if(!$this->targetIsPublic($data['entity_type'],(int)$data['entity_id'])) throw ValidationException::withMessages(['entity_id'=>'Select an active, public entity.']);
        }
        return $data;
    }

    private function targetIsPublic(string $type,int $id): bool
    {
        return match($type) {
            'car'=>CarModel::active()->whereKey($id)->exists(),
            'brand'=>Brand::active()->whereKey($id)->exists(),
            'category'=>Category::active()->whereKey($id)->exists(),
            'article'=>Article::whereKey($id)->where('is_deleted','no')->where('status','active')->where('publication_status','published')->whereNotNull('published_at')->where('published_at','<=',now())->exists(),
            default=>false,
        };
    }

    private function authorizeMedia(Request $request,array $data,?SeoMeta $seo=null): void
    {
        foreach(['og_image_id'=>'og_image_id','twitter_image_id'=>'twitter_image_id'] as $input=>$field) {
            $new=$data[$input]??null;
            $current=$seo ? $seo->{$field} : null;
            if($new && (int)$new !== (int)($current ?? 0)) abort_unless($request->user()->hasPermission('media.view'),403);
        }
    }

    private function modelFor(string $type): string
    {
        return match($type) {'car'=>CarModel::class,'brand'=>Brand::class,'category'=>Category::class,'article'=>Article::class,default=>throw ValidationException::withMessages(['entity_type'=>'Invalid SEO entity type.'])};
    }

    private function storedType(string $type): string { return $type==='homepage' ? 'homepage' : $this->modelFor($type); }
    private function publicType(string $stored): ?string { foreach(['car','brand','category','article'] as $type) if($this->modelFor($type)===$stored) return $type; return $stored==='homepage'?'homepage':null; }

    private function resource(SeoMeta $seo, array $labels=[]): array
    {
        $type=$this->publicType($seo->seoable_type);
        $entity=$labels[$seo->seoable_type.'|'.$seo->seoable_id] ?? null;
        if($entity===null && $type!=='homepage') $entity=$this->targetEntity($seo);
        $label=$type==='homepage'?'Homepage':(is_string($entity)?$entity:($entity?->name ?? $entity?->title ?? 'Unavailable entity'));
        return ['id'=>$seo->id,'entity_type'=>$type,'entity_id'=>$seo->seoable_id,'entity_label'=>$label,'meta_title'=>$seo->meta_title,'meta_description'=>$seo->meta_description,'canonical_url'=>$seo->canonical_url,'og_title'=>$seo->og_title,'og_description'=>$seo->og_description,'og_image'=>$seo->ogImage ? ['id'=>$seo->ogImage->id,'url'=>$seo->ogImage->url,'alt_text'=>$seo->ogImage->alt_text] : null,'robots'=>$seo->robots,'twitter_card_type'=>$seo->twitter_card_type,'twitter_title'=>$seo->twitter_title,'twitter_description'=>$seo->twitter_description,'twitter_image'=>$seo->twitterImage ? ['id'=>$seo->twitterImage->id,'url'=>$seo->twitterImage->url,'alt_text'=>$seo->twitterImage->alt_text] : null,'updated_at'=>$seo->updated_at];
    }

    private function targetEntity(SeoMeta $seo)
    {
        $type=$this->publicType($seo->seoable_type);
        if(!$type || $type==='homepage') return null;
        $model=$this->modelFor($type);
        return $model::find($seo->seoable_id);
    }

    private function entityLabels($rows): array
    {
        $labels=[];
        foreach(['car','brand','category','article'] as $type) {
            $class=$this->modelFor($type);
            $ids=$rows->where('seoable_type',$class)->pluck('seoable_id');
            if($ids->isEmpty()) continue;
            foreach($class::whereIn('id',$ids)->get() as $entity) $labels[$class.'|'.$entity->id]=$entity->name ?? $entity->title;
        }
        return $labels;
    }
}
