<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedContent;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeaturedContentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search'=>['nullable','string','max:255'],'status'=>['nullable',Rule::in(['active','inactive'])],'placement'=>['nullable',Rule::in(FeaturedContent::PLACEMENTS)]]);
        $query = FeaturedContent::with('media')->when($request->filled('search'), fn($q) => $q->where('title','like','%'.addcslashes($request->input('search'),'%_').'%'))
            ->when($request->filled('status'), fn($q) => $q->where('status',$request->input('status')))
            ->when($request->filled('placement'), fn($q) => $q->where('placement',$request->input('placement')))
            ->orderBy('display_order')->orderByDesc('id');
        $items = $this->paginateRequest($request, $query);
        if ($request->expectsJson()) return response()->json(['data'=>$items->through(fn($item) => $this->resource($item))]);
        return view('admin.featured-content.index', ['items'=>$items,'filters'=>$request->only('search','status','placement'),'placements'=>FeaturedContent::PLACEMENTS,'page_title'=>'Featured Content','leftMenuActive'=>'featured-content']);
    }

    public function create()
    {
        return view('admin.featured-content.form', ['item'=>new FeaturedContent(['status'=>'inactive','placement'=>'homepage_promotion']),'placements'=>FeaturedContent::PLACEMENTS,'media'=>null,'page_title'=>'Create Featured Content','leftMenuActive'=>'featured-content']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        abort_unless($request->user()->hasPermission('media.view'), 403);
        $item = FeaturedContent::create($data)->load('media');
        if ($request->expectsJson()) return response()->json(['data'=>$this->resource($item)],201);
        return redirect()->route('admin.featured-content.index')->with('status','Featured content created.');
    }

    public function show(FeaturedContent $featuredContent)
    {
        $featuredContent->load('media');
        return response()->json(['data'=>$this->resource($featuredContent)]);
    }

    public function edit(FeaturedContent $featuredContent)
    {
        return view('admin.featured-content.form', ['item'=>$featuredContent->load('media'),'placements'=>FeaturedContent::PLACEMENTS,'media'=>$featuredContent->media,'page_title'=>'Edit Featured Content','leftMenuActive'=>'featured-content']);
    }

    public function update(Request $request, FeaturedContent $featuredContent)
    {
        $data = $this->validated($request);
        abort_unless((int)$data['media_id'] === (int)$featuredContent->media_id || $request->user()->hasPermission('media.view'),403);
        $featuredContent->update($data);
        $featuredContent->load('media');
        if ($request->expectsJson()) return response()->json(['data'=>$this->resource($featuredContent)]);
        return redirect()->route('admin.featured-content.index')->with('status','Featured content updated.');
    }

    public function destroy(Request $request, FeaturedContent $featuredContent)
    {
        $featuredContent->delete();
        return $request->expectsJson() ? response()->noContent() : redirect()->route('admin.featured-content.index')->with('status','Featured content deleted.');
    }

    public function publicIndex(Request $request)
    {
        $request->validate(['placement'=>['nullable',Rule::in(FeaturedContent::PLACEMENTS)]]);
        $items = FeaturedContent::with('media')->where('status','active')->when($request->filled('placement'),fn($q)=>$q->where('placement',$request->input('placement')))->orderBy('display_order')->orderBy('id')->limit(100)->get();
        return response()->json(['data'=>$items->map(fn($item)=>$this->resource($item,false))->values()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'=>['required','string','max:255','not_regex:/^\s*$/'], 'description'=>['nullable','string','max:2000'],
            'media_id'=>['required','integer',Rule::exists('media','id')->where(fn($q)=>$q->where('disk','media')->whereNull('blog_id')->whereNotNull('created_by'))],
            'cta_text'=>['nullable','string','max:100'],'cta_url'=>['nullable','string','max:2048','regex:/^(\/(?!\/)[^\s]*|https?:\/\/[^\s]+)?$/i'],
            'placement'=>['required',Rule::in(FeaturedContent::PLACEMENTS)],'status'=>['required',Rule::in(['active','inactive'])],
            'display_order'=>['required','integer','between:0,65535'],
        ]);
    }

    private function resource(FeaturedContent $item, bool $admin = true): array
    {
        $result = ['id'=>$item->id,'title'=>$item->title,'description'=>$item->description,'image'=>$item->media ? ['id'=>$item->media->id,'url'=>$item->media->url,'alt_text'=>$item->media->alt_text] : null,'cta'=>['text'=>$item->cta_text,'url'=>$item->cta_url],'placement'=>$item->placement];
        if ($admin) $result += ['status'=>$item->status,'display_order'=>$item->display_order,'created_at'=>$item->created_at,'updated_at'=>$item->updated_at];
        return $result;
    }
}
