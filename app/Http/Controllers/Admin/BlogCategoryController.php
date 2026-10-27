<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\BlogCategory;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller {

    protected $categoryModel;

    public function __construct() {
        $this->categoryModel = new BlogCategory();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request) {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $query = $this->categoryModel::query()->where('is_deleted', 'no')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.addcslashes(trim($request->input('search')), '%_').'%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('sub_title', 'like', $term)->orWhere('slug', 'like', $term));
            });

        return view('admin.blogCategory.blogCategoriesList', [
            'blogCategories' => $this->paginateRequest($request, $query->latest()),
            'page_title' => 'Blog Categories', 'pageType' => 'list', 'leftMenuActive' => 'blog-category',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create() {

        $data = [];
        $data['page_title'] = 'Add New Blog Category';
        $data['pageType'] = 'add';
        $data['leftMenuActive'] = 'blog-category';
        $data['formAction'] = url('admin/blog-categories/store');
        $data['categoryInfo'] = null;
        return view('admin.blogCategory.blogCategoryForm', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please Try again later.';
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'sub_title' => 'required',
            'meta_title' => 'nullable',
            'slug' => [
                'required',
                Rule::unique('blog_categories', 'slug')->ignore($request->filled('id') ? $request->id : null),
            ],
            'meta_description' => 'nullable',
            'description' => 'nullable',
            'image' => 'nullable|file|max:'.config('media.max_image_kb', 5120),
            'image_media_id' => ['nullable', Rule::exists('media', 'id')->where(fn ($query) => $query->where('disk', 'media')->whereNull('blog_id')->whereNotNull('created_by'))],
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            $message = 'Please enter valid data.';
            $json['errors'] = $validator->errors();
            $json['success'] = false;
            $json['messageClass'] = $msgClass;
            $json['message'] = $message;
            return response()->json($json);
        }

        abort_unless(! $request->filled('image_media_id') || $request->user()->hasPermission('media.view'), 403);

        $customImageName = NULL;
        $uploadedImagePath = null;
        if ($request->hasFile('image')) {
            $uploadedImagePath = app(MediaService::class)->store($request->file('image'), (int) $request->user()->id, null, 'media', 'image')->path;
            $customImageName = $uploadedImagePath;
        } elseif ($request->filled('image_media_id')) {
            $customImageName = Media::where('disk', 'media')->findOrFail($request->input('image_media_id'))->path;
        }

        try {
            $blogCategoryModel = new BlogCategory();
            $blogCategoryModel->title = $request->title;
            $blogCategoryModel->sub_title = $request->sub_title;
            $blogCategoryModel->meta_title = $request->meta_title;
            $blogCategoryModel->slug = $request->slug;
            $blogCategoryModel->meta_description = $request->meta_description;
            $blogCategoryModel->description = $request->description;
            $blogCategoryModel->image = $customImageName;
            $blogCategoryModel->status = $request->status;
            $blogCategoryModel->save();

            $status = true;
            $msgClass = 'alert-success';
            $message = 'Blog Category has been added successfully!';
        } catch (\Throwable $e) {
            if ($uploadedImagePath) app(MediaService::class)->cleanupNewUpload($uploadedImagePath);
            Log::error('BlogCategory Store Error: ' . $e->getMessage());
        }

        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($id) {

        $data = [];
        $data['page_title'] = 'Edit Blog Category';
        $data['pageType'] = 'edit';
        $data['leftMenuActive'] = 'blog-category';
        $data['formAction'] = url('admin/blog-categories/update');
        $data['categoryInfo'] = $this->categoryModel->find($id);
        return view('admin.blogCategory.blogCategoryForm', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) {
        $postInfo = sanitizeInputArray($request->all());
        $request->merge($postInfo);
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please Try again later.';
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:blog_categories,id',
            'title' => 'required',
            'sub_title' => 'required',
            'meta_title' => 'nullable',
            'slug' => [
                'required',
                Rule::unique('blog_categories', 'slug')->ignore($request->filled('id') ? $request->id : null),
            ],
            'meta_description' => 'nullable',
            'description' => 'nullable',
            'image' => 'nullable|file|max:'.config('media.max_image_kb', 5120),
            'image_media_id' => ['nullable', Rule::exists('media', 'id')->where(fn ($query) => $query->where('disk', 'media')->whereNull('blog_id')->whereNotNull('created_by'))],
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            $message = 'Please enter valid data.';
            $json['errors'] = $validator->errors();
            $json['success'] = false;
            $json['messageClass'] = $msgClass;
            $json['message'] = $message;
            return response()->json($json);
        }

        abort_unless(! $request->filled('image_media_id') || $request->user()->hasPermission('media.view'), 403);

        $CategoryInfo = $this->categoryModel->findOrFail($request->id);

        $customImageName = $CategoryInfo->image ?? NULL;
        $oldImageName = $CategoryInfo->image;
        $uploadedImagePath = null;
        if ($request->hasFile('image')) {
            $uploadedImagePath = app(MediaService::class)->store($request->file('image'), (int) $request->user()->id, null, 'media', 'image')->path;
            $customImageName = $uploadedImagePath;
        } elseif ($request->filled('image_media_id')) {
            $customImageName = Media::where('disk', 'media')->findOrFail($request->input('image_media_id'))->path;
        }

        try {

            /* $blogCategoryModel = $this->categoryModel; */
            $CategoryInfo->title = $request->title;
            $CategoryInfo->sub_title = $request->sub_title;
            $CategoryInfo->meta_title = $request->meta_title;
            $CategoryInfo->slug = $request->slug;
            $CategoryInfo->meta_description = $request->meta_description;
            $CategoryInfo->description = $request->description;
            $CategoryInfo->image = $customImageName;
            $CategoryInfo->status = $request->status;
            $CategoryInfo->save();
            if (($request->hasFile('image') || $request->filled('image_media_id')) && $oldImageName) {
                $oldMedia = Media::where('disk', 'media')->where('path', $oldImageName)->first();
                if ($oldMedia) app(MediaService::class)->deleteIfUnreferenced($oldMedia);
            }

            $status = true;
            $msgClass = 'alert-success';
            $message = 'Blog Category has been updated successfully!';
        } catch (\Throwable $e) {
            if ($uploadedImagePath) app(MediaService::class)->cleanupNewUpload($uploadedImagePath);
            Log::error('BlogCategory Update Error: ' . $e->getMessage());
        }

        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please Try again later.';
        $id = isset($request->record_id)?$request->record_id:'';
        $categoryInfo = $this->categoryModel->findOrFail($id);
        if (!empty($categoryInfo)) {
            $categoryInfo->blogs()->detach();
            if (isset($categoryInfo->is_deleted) && $categoryInfo->is_deleted == 'no') {
                $categoryInfo->is_deleted = 'yes';
                $categoryInfo->deleted_at = now();
            }
            $categoryInfo->save();
            $status = true;
            $msgClass = 'alert-success';
            $message = 'Blog Category has been removed successfully!';
            $json['redirectURL'] = url('admin/blog-categories');
        }
        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }
}
