<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Brand, CarModel, Category, FeaturedContent, HomepageSection, Media};
use App\Models\Admin\Blogs as Article;
use App\Models\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\SeoMetadataService;

class HomepageController extends Controller
{
    private const DEFAULTS = [
        'hero' => ['title' => 'Find Your Perfect Car', 'enabled' => true, 'display_order' => 1, 'item_limit' => null, 'config' => ['heading' => 'Find Your Perfect Car', 'subheading' => '', 'media_id' => null, 'cta_text' => 'Explore Cars', 'cta_url' => '/cars']],
        'featured_cars' => ['title' => 'Featured Cars', 'enabled' => true, 'display_order' => 2, 'item_limit' => 6, 'config' => ['item_ids' => []]],
        'popular_brands' => ['title' => 'Popular Brands', 'enabled' => true, 'display_order' => 3, 'item_limit' => 8, 'config' => ['item_ids' => []]],
        'latest_articles' => ['title' => 'Latest Articles', 'enabled' => true, 'display_order' => 4, 'item_limit' => 6, 'config' => []],
        'car_categories' => ['title' => 'Explore Cars', 'enabled' => true, 'display_order' => 5, 'item_limit' => 8, 'config' => ['item_ids' => []]],
        'promotion' => ['title' => 'Promotion', 'enabled' => false, 'display_order' => 6, 'item_limit' => null, 'config' => ['description' => '', 'media_id' => null, 'cta_text' => '', 'cta_url' => '']],
    ];

    public function index()
    {
        $sections = $this->sections();
        return view('admin.homepage.index', [
            'sections' => $sections,
            'cars' => CarModel::with('brand:id,name')->ordered()->limit(100)->get(['id','brand_id','name','status']),
            'brands' => Brand::ordered()->limit(100)->get(['id','name','status']),
            'categories' => Category::active()->ordered()->limit(100)->get(['id','name']),
            'media' => Media::query()->where('mime', 'like', 'image/%')->latest()->limit(100)->get(['id','original_name','path','disk']),
            'page_title' => 'Homepage Management', 'leftMenuActive' => 'homepage',
        ]);
    }

    public function show()
    {
        echo '<pre>'; print_r($this->sections()); die;
        return response()->json(['data' => $this->sections()->values()]);
    }

    public function options(Request $request, string $type)
    {
        abort_unless(in_array($type, ['cars','brands','categories'], true), 404);
        $request->validate(['search'=>['nullable','string','max:100'],'selected'=>['nullable','array','max:50'],'selected.*'=>['integer']]);
        $term = trim((string)$request->input('search',''));
        if ($type === 'cars') {
            $query = CarModel::query()->with('brand:id,name')->when($term,fn($q)=>$q->where('name','like','%'.addcslashes($term,'%_').'%')->orWhere('slug','like','%'.addcslashes($term,'%_').'%'));
            $label = fn($m)=>$m->name.' — '.($m->brand?->name ?? '');
        } elseif ($type === 'brands') {
            $query = Brand::query()->when($term,fn($q)=>$q->where('name','like','%'.addcslashes($term,'%_').'%')->orWhere('slug','like','%'.addcslashes($term,'%_').'%'));
            $label = fn($m)=>$m->name;
        } else {
            $query = Category::query()->active()->when($term,fn($q)=>$q->where('name','like','%'.addcslashes($term,'%_').'%')->orWhere('slug','like','%'.addcslashes($term,'%_').'%'));
            $label = fn($m)=>$m->name;
        }
        $matches = $query->ordered()->limit(30)->get();
        $selected = empty($request->input('selected')) ? collect() : ($type === 'cars' ? CarModel::with('brand:id,name')->whereIn('id',$request->input('selected'))->get() : ($type === 'brands' ? Brand::whereIn('id',$request->input('selected'))->get() : Category::whereIn('id',$request->input('selected'))->get()));
        return response()->json(['data'=>$matches->merge($selected)->unique('id')->values()->map(fn($m)=>['value'=>$m->id,'label'=>$label($m),'description'=>($m->status ?? 'active')])]);
    }

    public function update(Request $request)
    {
        $rules = ['sections' => ['required','array','size:6']];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $p = "sections.{$key}";
            $rules += ["{$p}" => ['required','array'], "{$p}.enabled" => ['required','boolean'], "{$p}.title" => ['nullable','string','max:255'], "{$p}.display_order" => ['required','integer','between:0,65535'], "{$p}.item_limit" => ['nullable','integer','between:1,50']];
        }
        $rules += [
            'sections.hero.config.heading' => ['required','string','max:255'], 'sections.hero.config.subheading' => ['nullable','string','max:1000'], 'sections.hero.config.media_id' => ['nullable','integer',Rule::exists('media','id')->where(fn ($query) => $query->where('disk', 'media')->where('mime', 'like', 'image/%')->whereNull('blog_id')->whereNotNull('created_by'))], 'sections.hero.config.cta_text' => ['nullable','string','max:100'], 'sections.hero.config.cta_url' => ['nullable','string','max:2048','regex:/^(\/(?!\/)[^\s]*|https?:\/\/[^\s]+)?$/i'],
            'sections.promotion.config.description' => ['nullable','string','max:2000'], 'sections.promotion.config.media_id' => ['nullable','integer',Rule::exists('media','id')->where(fn ($query) => $query->where('disk', 'media')->where('mime', 'like', 'image/%')->whereNull('blog_id')->whereNotNull('created_by'))], 'sections.promotion.config.cta_text' => ['nullable','string','max:100'], 'sections.promotion.config.cta_url' => ['nullable','string','max:2048','regex:/^(\/(?!\/)[^\s]*|https?:\/\/[^\s]+)?$/i'],
        ];
        foreach (['featured_cars' => ['car_models','id'], 'popular_brands' => ['brands','id'], 'car_categories' => ['categories','id']] as $key => [$table,$column]) {
            $rules["sections.{$key}.config.item_ids"] = ['nullable','array','max:50'];
            $rules["sections.{$key}.config.item_ids.*"] = ['integer','distinct',Rule::exists($table,$column)];
        }
        $data = $request->validate($rules);
        $usesMedia = collect(['hero', 'promotion'])->contains(fn ($key) => ! empty($data['sections'][$key]['config']['media_id']));
        abort_unless(! $usesMedia || $request->user()->hasPermission('media.view'), 403);
        DB::transaction(function () use ($data) {
            foreach (self::DEFAULTS as $key => $default) {
                $item = $data['sections'][$key];
                $config = $item['config'] ?? [];
                if ($key === 'latest_articles') $config = [];
                HomepageSection::updateOrCreate(['section_key' => $key], [
                    'title' => $key === 'hero' ? ($config['heading'] ?? $item['title'] ?? $default['title']) : ($item['title'] ?? $default['title']),
                    'enabled' => $item['enabled'], 'display_order' => $item['display_order'],
                    'item_limit' => $item['item_limit'] ?? $default['item_limit'], 'config' => $config,
                ]);
            }
        });
        if ($request->expectsJson()) return response()->json(['data' => $this->sections()->values()]);
        return redirect()->route('admin.homepage.index')->with('status', 'Homepage configuration saved.');
    }

    public function publicIndex()
    {
        $sections = $this->sections()->keyBy('section_key');
        $result = [];
        foreach (self::DEFAULTS as $key => $default) {
            $section = (object)$sections->get($key);

            $config = isset($section?->config) && !empty($section?->config) ? $section?->config : $default['config'];

            $entry = ['enabled' => (bool) ($section?->enabled ?? $default['enabled']), 'title' => $section?->title ?? $default['title'], 'display_order' => $section?->display_order ?? $default['display_order']];
            $limit = min(50, max(1, (int) ($section?->item_limit ?? $default['item_limit'] ?? 50)));
            if ($key === 'hero' || $key === 'promotion') {
                $entry = array_merge($entry, $key === 'hero' ? ['title' => $config['heading'] ?? $entry['title'], 'subtitle' => $config['subheading'] ?? null] : ['description' => $config['description'] ?? null]);
                $entry['image'] = !empty($config['media_id']) ? $this->mediaResource(Media::find($config['media_id'])) : null;
                $entry['cta'] = ['text' => $config['cta_text'] ?? null, 'url' => $config['cta_url'] ?? null];
                if ($key === 'hero') $entry['banners'] = FeaturedContent::with('media')->where('status','active')->where('placement','homepage_hero')->orderBy('display_order')->orderBy('id')->limit($limit)->get()->map(fn($banner) => $this->bannerResource($banner));
                else $entry['items'] = FeaturedContent::with('media')->where('status','active')->where('placement','homepage_promotion')->orderBy('display_order')->orderBy('id')->limit($limit)->get()->map(fn($banner) => $this->bannerResource($banner));
            } elseif ($key === 'featured_cars') {

                $entry['items'] = CarModel::active()->whereIn('id', $config['item_ids'] ?? [])->with('brand:id,name,slug,logo')->get()->sortBy(fn($m) => array_search($m->id, $config['item_ids'] ?? []))->take($limit)->values()->map(fn($m) => ['id'=>$m->id,'name'=>$m->name,'slug'=>$m->slug,'status'=>$m->status,'brand'=>$m->brand ? ['id'=>$m->brand->id,'name'=>$m->brand->name,'slug'=>$m->brand->slug] : null,'image'=>media_image_url($m->thumbnail,'car_media')]);
                $entry['banners'] = FeaturedContent::with('media')->where('status','active')->where('placement','homepage_featured')->orderBy('display_order')->orderBy('id')->limit($limit)->get()->map(fn($banner) => $this->bannerResource($banner));
            } elseif ($key === 'popular_brands') {
                $entry['items'] = Brand::active()->whereIn('id',$config['item_ids'] ?? [])->get(['id','name','slug','logo'])->sortBy(fn($m) => array_search($m->id,$config['item_ids'] ?? []))->take($limit)->values()->map(fn($b) => ['id'=>$b->id,'name'=>$b->name,'slug'=>$b->slug,'image'=>media_image_url($b->logo,'brand_media')]);
            } elseif ($key === 'car_categories') {
                $entry['items'] = Category::active()->whereIn('id',$config['item_ids'] ?? [])->get(['id','name','slug','image'])->sortBy(fn($m) => array_search($m->id,$config['item_ids'] ?? []))->take($limit)->values()->map(fn($b) => ['id'=>$b->id,'name'=>$b->name,'slug'=>$b->slug,'image'=>media_image_url($b->image,'brand_media')]);
            } else {
                $entry['items'] = Article::query()->where('is_deleted','no')->where('publication_status','published')->where('status','active')->whereNotNull('published_at')->where('published_at','<=',now())->with(['category:id,name,slug','author:id,name'])->latest('published_at')->limit($limit)->get(['id','title','slug','excerpt','featured_image','category_id','user_id','published_at'])->map(fn($a) => ['id'=>$a->id,'title'=>$a->title,'slug'=>$a->slug,'excerpt'=>$a->excerpt,'image'=>media_image_url($a->featured_image,'brand_media'),'category'=>$a->category ? ['id'=>$a->category->id,'name'=>$a->category->name,'slug'=>$a->category->slug] : null,'author'=>$a->author?->name,'published_at'=>$a->published_at]);
            }
            if ($entry['enabled']) $result[$key] = $entry;
        }
        $homepageSeo = SeoMeta::with(['ogImage','twitterImage'])->where('seoable_type','homepage')->where('seoable_id',1)->first();
        uasort($result, fn($a,$b) => ($a['display_order'] ?? PHP_INT_MAX) <=> ($b['display_order'] ?? PHP_INT_MAX));
        $result['seo'] = app(SeoMetadataService::class)->metadata('homepage', null, $homepageSeo);
        return response()->json(['data' => $result]);
    }

    private function sections()
    {
        $saved = HomepageSection::all()->keyBy('section_key');
        return collect(self::DEFAULTS)->map(function ($default, $key) use ($saved) {
            $model = $saved->get($key);

            return ['section_key'=>$key,'title'=>$model?->title ?? $default['title'],'enabled'=>$model?->enabled ?? $default['enabled'],'display_order'=>$model?->display_order ?? $default['display_order'],'item_limit'=>$model?->item_limit ?? $default['item_limit'],'config'=>$model?->config ?? $default['config']];
        });
    }

    private function mediaResource(?Media $media): ?array
    {
        return $media ? ['id'=>$media->id,'url'=>$media->url,'alt_text'=>$media->alt_text] : null;
    }

    private function bannerResource(FeaturedContent $banner): array
    {
        return ['id'=>$banner->id,'title'=>$banner->title,'description'=>$banner->description,'image'=>$banner->media ? ['id'=>$banner->media->id,'url'=>$banner->media->url,'alt_text'=>$banner->media->alt_text] : null,'cta'=>['text'=>$banner->cta_text,'url'=>$banner->cta_url],'placement'=>$banner->placement];
    }
}
