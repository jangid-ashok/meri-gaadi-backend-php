<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Blogs as Article;
use App\Models\Admin\Tag;
use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use App\Services\SeoMetadataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    private const STATUSES = ['draft', 'published', 'inactive'];

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'tag_id' => ['nullable', 'integer', Rule::exists('tags', 'id')],
            'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $articles = $this->paginateRequest($request, $this->query($request));
        if ($request->expectsJson()) {
            $articles->getCollection()->transform(fn (Article $article) => $this->resource($article));
            return response()->json(['data' => $articles]);
        }

        return view('admin.articles.index', [
            'articles' => $articles,
            'categories' => Category::active()->ordered()->get(['id', 'name', 'status']),
            'tags' => Tag::active()->ordered()->get(['id', 'name', 'status']),
            'authors' => $this->eligibleAuthors(),
            'page_title' => 'Articles',
            'leftMenuActive' => 'articles',
        ]);
    }

    public function create()
    {
        return view('admin.articles.form', $this->formData(new Article(), 'Create article'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $mediaId = $data['featured_image_media_id'] ?? null;
        abort_unless(! $mediaId || $request->user()->hasPermission('media.view'), 403);
        $this->authorizePublication($request, null, $data['status']);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);
        $data['user_id'] = $data['author_id'];
        $data['description'] = $data['content'];
        $data['status'] = $data['status'] === 'published' ? 'active' : 'inactive';
        $data['publication_status'] = $request->input('status');
        $data['published_at'] = $this->publicationDate($request, null, $data['publication_status']);
        unset($data['author_id'], $data['content'], $data['tags'], $data['tags_present'], $data['clear_featured_image'], $data['featured_image_media_id']);

        $uploadedImage = $request->hasFile('featured_image')
            ? app(MediaService::class)->store($request->file('featured_image'), (int) $request->user()->id, null, 'media', 'featured_image')->path
            : null;
        $imagePath = $request->hasFile('featured_image')
            ? $uploadedImage
            : ($mediaId ? Media::where('disk', 'media')->findOrFail($mediaId)->path : null);
        $data['featured_image'] = $imagePath;
        try {
            $article = DB::transaction(function () use ($data, $request) {
                $article = Article::create($data);
                $article->tags()->sync($request->input('tags', []));
                return $article;
            });
        } catch (\Throwable $exception) {
            if ($uploadedImage) app(MediaService::class)->cleanupNewUpload($uploadedImage);
            throw $exception;
        }

        $article->load(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name,email']);
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($article)], 201);
        return redirect()->route('admin.articles.show', $article)->with('status', 'Article created successfully.');
    }

    public function show(Request $request, Article $article)
    {
        abort_if($article->is_deleted !== 'no', 404);
        $article->load(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name,email']);
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($article)]);
        return view('admin.articles.show', ['article' => $article, 'page_title' => $article->title, 'leftMenuActive' => 'articles']);
    }

    public function edit(Article $article)
    {
        abort_if($article->is_deleted !== 'no', 404);
        return view('admin.articles.form', $this->formData($article->load(['tags:id,name,status']), 'Edit article'));
    }

    public function update(Request $request, Article $article)
    {
        abort_if($article->is_deleted !== 'no', 404);
        $data = $this->validated($request, $article);
        $mediaId = $data['featured_image_media_id'] ?? null;
        abort_unless(! $mediaId || $request->user()->hasPermission('media.view'), 403);
        $this->authorizePublication($request, $article, $data['status']);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $article);
        $data['user_id'] = $data['author_id'];
        $data['description'] = $data['content'];
        $data['status'] = $data['status'] === 'published' ? 'active' : 'inactive';
        $data['publication_status'] = $request->input('status');
        $data['published_at'] = $this->publicationDate($request, $article, $data['publication_status']);
        unset($data['author_id'], $data['content'], $data['tags'], $data['tags_present'], $data['clear_featured_image'], $data['featured_image_media_id']);

        $oldImage = $article->featured_image;
        $uploadedImage = $request->hasFile('featured_image')
            ? app(MediaService::class)->store($request->file('featured_image'), (int) $request->user()->id, null, 'media', 'featured_image')->path
            : null;
        $newImage = $request->hasFile('featured_image')
            ? $uploadedImage
            : ($mediaId ? Media::where('disk', 'media')->findOrFail($mediaId)->path : null);
        if ($newImage !== null) $data['featured_image'] = $newImage;
        elseif ($request->boolean('clear_featured_image')) $data['featured_image'] = null;

        try {
            DB::transaction(function () use ($article, $data, $request) {
                $article->update($data);
                if ($request->has('tags') || $request->boolean('tags_present')) {
                    $article->tags()->sync($request->input('tags', []));
                }
            });
        } catch (\Throwable $exception) {
            if ($uploadedImage) app(MediaService::class)->cleanupNewUpload($uploadedImage);
            throw $exception;
        }

        if (($newImage || $request->boolean('clear_featured_image')) && $oldImage) $this->removeImage($oldImage);
        $article->refresh()->load(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name,email']);
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($article)]);
        return redirect()->route('admin.articles.show', $article)->with('status', 'Article updated successfully.');
    }

    public function status(Request $request, Article $article)
    {
        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);
        $this->authorizePublication($request, $article, $data['status']);
        $article->publication_status = $data['status'];
        $article->status = $data['status'] === 'published' ? 'active' : 'inactive';
        $article->published_at = $this->publicationDate($request, $article, $data['status']);
        $article->save();
        $article->load(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name,email']);
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($article)]);
        return back()->with('status', 'Article status updated successfully.');
    }

    public function destroy(Request $request, Article $article)
    {
        $oldImage = $article->featured_image;
        $article->tags()->detach();
        $article->is_deleted = 'yes';
        $article->save();
        $article->delete();
        if ($oldImage) $this->removeImage($oldImage);
        if ($request->expectsJson()) return response()->noContent();
        return redirect()->route('admin.articles.index')->with('status', 'Article deleted successfully.');
    }

    public function publicIndex(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
        ]);
        $articles = $this->paginateRequest($request, Article::query()
            ->where('is_deleted', 'no')->where('publication_status', 'published')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->with(['category:id,name,slug,status,deleted_at', 'tags:id,name,slug,status', 'author:id,name', 'seoMeta.ogImage', 'seoMeta.twitterImage'])
            ->when($request->filled('search'), fn ($q) => $this->applySearch($q, $request->input('search')))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($category) => $category->where('status', 'active')->where('slug', $request->input('category'))))
            ->when($request->filled('tag'), fn ($q) => $q->whereHas('tags', fn ($tag) => $tag->where('status', 'active')->where('slug', $request->input('tag'))))
            ->latest('published_at')->latest('id'));
        $articles->getCollection()->transform(fn (Article $article) => $this->publicResource($article));
        return response()->json(['data' => $articles]);
    }

    public function publicShow(string $slug)
    {
        $article = Article::query()->where('slug', $slug)->where('is_deleted', 'no')
            ->where('publication_status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
            ->with(['category:id,name,slug,status,deleted_at', 'tags:id,name,slug,status', 'author:id,name', 'seoMeta.ogImage', 'seoMeta.twitterImage'])->firstOrFail();
        return response()->json(['data' => $this->publicResource($article)]);
    }

    private function query(Request $request)
    {
        return Article::query()->where('is_deleted', 'no')
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name', 'seoMeta.ogImage', 'seoMeta.twitterImage'])
            ->when($request->filled('search'), fn ($q) => $this->applySearch($q, $request->input('search')))
            ->when($request->filled('status'), fn ($q) => $q->where('publication_status', $request->input('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->filled('tag_id'), fn ($q) => $q->whereHas('tags', fn ($t) => $t->whereKey($request->input('tag_id'))))
            ->when($request->filled('author_id'), fn ($q) => $q->where('user_id', $request->input('author_id')))
            ->latest('created_at');
    }

    private function applySearch($query, string $search)
    {
        $term = '%' . addcslashes(trim($search), '%_') . '%';
        return $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('slug', 'like', $term)->orWhere('excerpt', 'like', $term));
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) { if (trim($value) === '') $fail('The title field is required.'); }],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blogs', 'slug')->ignore($article)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string', function ($attribute, $value, $fail) { if (trim($value) === '') $fail('The content field is required.'); }],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'distinct', Rule::exists('tags', 'id'), function ($attribute, $value, $fail) use ($article) {
                $isActive = Tag::query()->whereKey($value)->where('status', 'active')->exists();
                $alreadyAssigned = $article && $article->tags()->whereKey($value)->exists();
                if (! $isActive && ! $alreadyAssigned) $fail('Only active tags may be newly assigned.');
            }],
            'tags_present' => ['nullable', 'boolean'],
            'author_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'status' => ['required', Rule::in(self::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'file', 'max:'.config('media.max_image_kb', 5120)],
            'featured_image_media_id' => ['nullable', Rule::exists('media', 'id')->where(fn ($query) => $query->where('disk', 'media')->whereNull('blog_id')->whereNotNull('created_by'))],
            'clear_featured_image' => ['nullable', 'boolean'],
        ]);
        $data['title'] = trim($data['title']);
        $data['content'] = trim($data['content']);
        $category = Category::withTrashed()->findOrFail($data['category_id']);
        abort_unless((! $category->trashed() && $category->status === 'active') || ($article && (int) $article->category_id === (int) $category->id), 422, 'Only active categories may be newly assigned.');
        abort_unless($this->eligibleAuthors()->contains('id', (int) $data['author_id']), 422, 'The selected author is not eligible to manage articles.');
        return $data;
    }

    private function authorizePublication(Request $request, ?Article $article, string $newStatus): void
    {
        $oldStatus = $article?->publication_status;
        if ($newStatus !== $oldStatus && ($newStatus === 'published' || $oldStatus === 'published')) {
            abort_unless($request->user()->hasPermission('articles.publish'), 403, 'You do not have permission to publish or unpublish articles.');
        }
    }

    private function publicationDate(Request $request, ?Article $article, string $status): ?string
    {
        $provided = $request->input('published_at');
        if ($provided) return $provided;
        if ($status === 'published') return $article?->published_at?->toDateTimeString() ?? now()->toDateTimeString();
        if ($article?->published_at) return $article->published_at->toDateTimeString();
        return null;
    }

    private function uniqueSlug(string $source, ?Article $article = null): string
    {
        $base = Str::slug($source) ?: 'article';
        $slug = $base;
        $suffix = 2;
        while (Article::withTrashed()->where('slug', $slug)->when($article, fn ($q) => $q->whereKeyNot($article->id))->exists()) $slug = $base . '-' . $suffix++;
        return $slug;
    }

    private function eligibleAuthors()
    {
        return User::query()->where('is_admin', true)->where('status', 'active')
            ->where(fn ($q) => $q->whereHas('roles', fn ($roles) => $roles->where('slug', 'super-admin'))
                ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('slug', 'articles.create')))
            ->orderBy('name')->get(['id', 'name', 'email']);
    }

    private function formData(Article $article, string $title): array
    {
        $categories = Category::active()->ordered()->get(['id', 'name', 'status']);
        $tags = Tag::active()->ordered()->get(['id', 'name', 'status']);
        if ($article->exists) {
            $article->loadMissing(['category:id,name,status,deleted_at', 'tags:id,name,status']);
            if ($article->category && ! $categories->contains('id', $article->category->id)) $categories->push($article->category);
            $tags = $tags->merge($article->tags)->unique('id')->sortBy('name')->values();
        }

        return [
            'article' => $article,
            'categories' => $categories,
            'tags' => $tags,
            'authors' => $this->eligibleAuthors(),
            'page_title' => $title,
            'leftMenuActive' => 'articles',
        ];
    }

    private function resource(Article $article): array
    {
        $seo = app(SeoMetadataService::class)->metadata('article', $article);
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'content' => $article->description,
            'featured_image' => $article->featured_image ? Storage::disk('brand_media')->url($article->featured_image) : null,
            'category' => $article->category?->only(['id', 'name', 'slug']),
            'tags' => $article->tags->map(fn (Tag $tag) => $tag->only(['id', 'name', 'slug']))->values(),
            'author' => $article->author?->only(['id', 'name']),
            'status' => $article->publication_status,
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
            'updated_at' => $article->updated_at,
            'meta_title' => $seo['title'],
            'meta_description' => $seo['description'],
            'seo' => $seo,
        ];
    }

    private function publicResource(Article $article): array
    {
        $resource = $this->resource($article);
        $resource['category'] = $article->category && ! $article->category->trashed() && $article->category->status === 'active'
            ? $resource['category']
            : null;
        $resource['tags'] = $article->tags->where('status', 'active')
            ->map(fn (Tag $tag) => $tag->only(['id', 'name', 'slug']))->values();

        return $resource;
    }

    private function removeImage(string $path): void
    {
        $media = Media::where('disk', 'media')->where('path', $path)->first();
        if (! $media) $media = Media::where('disk', 'brand_media')->where('path', $path)->first();
        if ($media) app(MediaService::class)->deleteIfUnreferenced($media);
        elseif (! Article::where('featured_image', $path)->exists() && ! \App\Models\Brand::where('logo', $path)->exists()) {
            Storage::disk('brand_media')->delete($path);
        }
    }
}
