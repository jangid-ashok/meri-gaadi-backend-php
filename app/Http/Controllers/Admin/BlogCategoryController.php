<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\BlogCategory;
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
    public function index() {
        $data = [];
        $data['page_title'] = 'Blog Categories';
        $data['pageType'] = 'list';
        $data['leftMenuActive'] = 'blog-category';
        return view('admin.blogCategory.blogCategoriesList', $data);
    }

    public function getCategoriesList(Request $request) {
        $data = [];
        $pageNo = (int) $request->get('page', 1);
        $keyword = $request->get('keyword');
        $page_limit = config('constants.PAGE_LIMIT');
        $query = $this->categoryModel::query();
        $query->where('is_deleted', 'no');
        if (!empty($keyword)) {
            $kw = '%' . $keyword . '%';
            $query->where(function ($q) use ($kw) {
                $q->where('title', 'like', $kw)
                    ->orWhere('sub_title', 'like', $kw)
                    ->orWhere('slug', 'like', $kw);
            });
        }
        $count = $query->count();
        // $query->limit($page_limit, ($pageNo - 1) * $page_limit);
        $categories = $query->latest()->skip(($pageNo - 1) * $page_limit)->take($page_limit)->get();
        // echo '<pre>';print_r($count);
        // echo '<pre>';print_r($categories);die;
        $data['blogCategories'] = $categories;
        $html = view('admin.blogCategory.getBlogCategoryList', $data)->render();
        return response()->json([
            'total_records' => $count,
            'html' => $html,
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp',
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

        $customImageName = NULL;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $customImageName = time().'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path(config('constants.BLOG_CATEGORY_IMAGE_PATH'));

            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Move the file manually
            $image->move($destinationPath, $customImageName);
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp',
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

        $CategoryInfo = $this->categoryModel->findOrFail($request->id);

        $customImageName = $CategoryInfo->image ?? NULL;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $customImageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path(config('constants.BLOG_CATEGORY_IMAGE_PATH'));

            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Move the file manually
            $image->move($destinationPath, $customImageName);
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

            $status = true;
            $msgClass = 'alert-success';
            $message = 'Blog Category has been updated successfully!';
        } catch (\Throwable $e) {
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
