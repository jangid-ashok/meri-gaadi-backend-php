<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ]);

        $faqs = $this->paginateRequest($request, $this->query($request)->with('category:id,name,category_type'));

        if ($request->expectsJson()) {
            return response()->json(['data' => $faqs]);
        }

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'categories' => Category::active()->ordered()->get(['id', 'name', 'category_type']),
            'page_title' => 'FAQs',
            'leftMenuActive' => 'faqs',
        ]);
    }

    public function active()
    {
        return response()->json(['data' => Faq::active()->ordered()->with('category:id,name')->get(['id', 'category_id', 'question', 'answer', 'slug', 'status', 'sort_order'])]);
    }

    public function create()
    {
        return view('admin.faqs.form', [
            'faq' => new Faq(['status' => 'active', 'sort_order' => 0]),
            'categories' => Category::active()->ordered()->get(['id', 'name', 'category_type']),
            'page_title' => 'Add FAQ',
            'leftMenuActive' => 'faqs',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['question'] = trim($data['question']);
        $data['answer'] = trim($data['answer']);
        $data['slug'] = $this->uniqueSlug($data['question']);

        $faq = Faq::create($data)->load('category:id,name,category_type');

        if ($request->expectsJson()) {
            return response()->json(['data' => $faq], 201);
        }

        return redirect()->route('admin.faqs.show', $faq)->with('status', 'FAQ created successfully.');
    }

    public function show(Faq $faq)
    {
        $faq->load('category:id,name,category_type');

        if (request()->expectsJson()) {
            return response()->json(['data' => $faq]);
        }

        return view('admin.faqs.show', [
            'faq' => $faq,
            'page_title' => $faq->question,
            'leftMenuActive' => 'faqs',
        ]);
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.form', [
            'faq' => $faq,
            'categories' => Category::active()->ordered()->get(['id', 'name', 'category_type']),
            'page_title' => 'Edit FAQ',
            'leftMenuActive' => 'faqs',
        ]);
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $this->validated($request, $faq);
        $data['question'] = trim($data['question']);
        $data['answer'] = trim($data['answer']);
        $data['slug'] = $this->uniqueSlug($data['question'], $faq);

        $faq->update($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $faq->fresh()->load('category:id,name,category_type')]);
        }

        return redirect()->route('admin.faqs.show', $faq)->with('status', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ deleted successfully.');
    }

    private function query(Request $request)
    {
        $query = Faq::query()->search($request->string('search')->toString());

        foreach (['category_id', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        $allowed = ['sort_order', 'question', 'created_at', 'status'];

        return in_array($sort, $allowed, true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'question', fn ($query) => $query->orderBy('question'))
            : $query->ordered();
    }

    private function validated(Request $request, ?Faq $faq = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'question' => ['required', 'string', 'max:500', function ($attribute, $value, $fail) use ($faq) {
                $question = trim((string) $value);
                if ($question === '') {
                    $fail('The question field must not be empty.');
                    return;
                }

                $exists = Faq::query()
                    ->whereRaw('LOWER(question) = ?', [mb_strtolower($question)])
                    ->when($faq, fn ($query) => $query->whereKeyNot($faq->getKey()))
                    ->exists();

                if ($exists) {
                    $fail('The question has already been taken.');
                }
            }],
            'answer' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $question, ?Faq $faq = null): string
    {
        $base = Str::slug($question) ?: 'faq';
        $slug = $base;
        $suffix = 2;

        while (Faq::withTrashed()->where('slug', $slug)->when($faq, fn ($query) => $query->whereKeyNot($faq->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
