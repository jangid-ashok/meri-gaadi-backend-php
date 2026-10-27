<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $tags = $this->paginateRequest($request, $this->query($request)->withCount('blogs'));

        if ($request->expectsJson()) {
            return response()->json(['data' => $tags]);
        }

        return view('admin.tags.index', [
            'tags' => $tags,
            'page_title' => 'Tags',
            'leftMenuActive' => 'tags',
        ]);
    }

    public function active()
    {
        return response()->json(['data' => Tag::active()->ordered()->get(['id', 'name', 'slug'])]);
    }

    public function create()
    {
        return view('admin.tags.form', [
            'tag' => new Tag(['status' => 'active', 'sort_order' => 0]),
            'page_title' => 'Add Tag',
            'leftMenuActive' => 'tags',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $tag = Tag::create($data)->loadCount('blogs');

        if ($request->expectsJson()) {
            return response()->json(['data' => $tag], 201);
        }

        return redirect()->route('admin.tags.show', $tag)->with('status', 'Tag created successfully.');
    }

    public function show(Tag $tag)
    {
        $tag->loadCount('blogs');

        if (request()->expectsJson()) {
            return response()->json(['data' => $tag]);
        }

        return view('admin.tags.show', [
            'tag' => $tag,
            'page_title' => $tag->name,
            'leftMenuActive' => 'tags',
        ]);
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.form', [
            'tag' => $tag,
            'page_title' => 'Edit Tag',
            'leftMenuActive' => 'tags',
        ]);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $this->validated($request, $tag);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name'], $tag);

        $tag->update($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $tag->fresh()->loadCount('blogs')]);
        }

        return redirect()->route('admin.tags.show', $tag)->with('status', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag)
    {
        if ($tag->blogs()->exists()) {
            abort(409, 'This tag is assigned to content and cannot be deleted.');
        }

        $tag->delete();

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.tags.index')->with('status', 'Tag deleted successfully.');
    }

    private function query(Request $request)
    {
        $query = Tag::query()->search($request->string('search')->toString());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        $allowed = ['sort_order', 'name', 'created_at', 'status'];

        return in_array($sort, $allowed, true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
            : $query->ordered();
    }

    private function validated(Request $request, ?Tag $tag = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) use ($tag) {
                $name = trim($value);
                if ($name === '') {
                    $fail('The name field must not be empty.');
                    return;
                }

                $exists = Tag::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->when($tag ?? null, fn ($query) => $query->whereKeyNot($tag->getKey()))
                    ->exists();

                if ($exists) {
                    $fail('The name has already been taken.');
                }
            }],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $name, ?Tag $tag = null): string
    {
        $base = Str::slug($name) ?: 'tag';
        $slug = $base;
        $suffix = 2;

        while (Tag::withTrashed()->where('slug', $slug)->when($tag, fn ($query) => $query->whereKeyNot($tag->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}