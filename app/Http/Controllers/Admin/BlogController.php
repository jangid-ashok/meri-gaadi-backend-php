<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Admin\BlogCategory;
use App\Models\Admin\BlogMedia;
use App\Models\Admin\Blogs;

class BlogController extends Controller {

    protected $categoryModel;
    protected $blogModel;

    public function __construct() {
        $this->categoryModel = new BlogCategory();
        $this->blogModel = new Blogs();
    }

    public function index() {
        $data = [];
        $data['page_title'] = 'Blogs';
        $data['pageType'] = 'list';
        $data['leftMenuActive'] = 'blogs';
        return view('admin/blogs/blogsList', $data);
    }

    public function getBlogsList(Request $request) {
        $data = [];
        $pageNo = (int) $request->get('page', 1);
        $keyword = $request->get('keyword');
        $page_limit = config('constants.PAGE_LIMIT');
        $query = $this->blogModel::query()->with('categories');
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
        $records = $query->latest()->skip(($pageNo - 1) * $page_limit)->take($page_limit)->get();
        // echo '<pre>';print_r($count);
        // echo '<pre>';print_r($records);die;
        $data['blogs'] = $records;
        $html = view('admin.blogs.getBlogList', $data)->render();
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
        $data['page_title'] = 'Add New Blog';
        $data['pageType'] = 'add';
        $data['leftMenuActive'] = 'blogs';
        $data['formAction'] = url('admin/blog/store');
        $data['categories'] = $this->categoryModel->where('status', 'active')->where('is_deleted', 'no')->latest('created_at')->get();
        return view('admin.blogs.blogForm', $data);
    }

    public function edit($id) {
        $data = [];
        $data['page_title'] = 'Edit Blog';
        $data['pageType'] = 'edit';
        $data['leftMenuActive'] = 'blogs';
        $data['formAction'] = url('admin/blog/update');
        $data['categories'] = $this->categoryModel->where('status', 'active')->where('is_deleted', 'no')->latest('created_at')->get();
        $blog = $this->blogModel::with('categories')->findOrFail($id);
        $data['blogInfo'] = $blog;
        $data['selectedCategoryId'] = optional($blog->categories->first())->id;

        return view('admin.blogs.blogForm', $data);
    }

    public function store(Request $request) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please Try again later.';
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required',
            'title' => 'required',
            'sub_title' => 'required',
            'meta_title' => 'nullable',
            'slug' => [
                'required',
                Rule::unique('blogs', 'slug')->ignore($request->filled('id') ? $request->id : null),
            ],
            'meta_description' => 'nullable',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            $message = 'Please enter the valid data.';
            $json['errors'] = $validator->errors();
            $json['success'] = false;
            $json['messageClass'] = $msgClass;
            $json['message'] = $message;
            return response()->json($json);
        }

        try {
            $blogModel = new Blogs();
            $blogModel->user_id = auth()->id();
            $blogModel->title = $request->title;
            $blogModel->sub_title = $request->sub_title;
            $blogModel->meta_title = $request->meta_title;
            $blogModel->slug = $request->slug;
            $blogModel->meta_description = $request->meta_description;
            $blogModel->description = $request->description;
            $blogModel->status = $request->status;
            $blogModel->published_at = $request->status === 'active' ? now() : null;
            $blogModel->save();

            $categoryId = (int) $request->category_ids;
            $blogModel->categories()->sync($categoryId > 0 ? [$categoryId] : []);

            $status = true;
            $msgClass = 'alert-success';
            $message = 'Blog has been added successfully!';
        } catch (\Throwable $e) {
            Log::error('Blog Store Error: ' . $e->getMessage());
        }

        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }

    public function update(Request $request) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please Try again later.';

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:blogs,id',
            'category_ids' => 'required',
            'title' => 'required',
            'sub_title' => 'required',
            'meta_title' => 'nullable',
            'slug' => [
                'required',
                Rule::unique('blogs', 'slug')->ignore($request->filled('id') ? $request->id : null),
            ],
            'meta_description' => 'nullable',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            $message = 'Please enter the valid data.';
            $json['errors'] = $validator->errors();
            $json['success'] = false;
            $json['messageClass'] = $msgClass;
            $json['message'] = $message;
            return response()->json($json);
        }

        try {
            $blog = $this->blogModel::findOrFail($request->id);
            $blog->title = $request->title;
            $blog->sub_title = $request->sub_title;
            $blog->meta_title = $request->meta_title;
            $blog->slug = $request->slug;
            $blog->meta_description = $request->meta_description;
            $blog->description = $request->description;
            $blog->status = $request->status;

            if ($request->status === 'active') {
                $blog->published_at = $blog->published_at ?? now();
            } else {
                $blog->published_at = null;
            }

            $blog->save();

            $categoryId = (int) $request->category_ids;
            $blog->categories()->sync($categoryId > 0 ? [$categoryId] : []);

            $status = true;
            $msgClass = 'alert-success';
            $message = 'Blog has been updated successfully!';
        } catch (\Throwable $e) {
            Log::error('Blog Update Error: ' . $e->getMessage());
        }

        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }

    public function destroy(Request $request) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please Try again later.';
        $id = isset($request->record_id) ? $request->record_id : '';
        $blog = $this->blogModel->findOrFail($id);
        if (!empty($blog)) {
            $blog->categories()->detach();
            if (isset($blog->is_deleted) && $blog->is_deleted === 'no') {
                $blog->is_deleted = 'yes';
                $blog->deleted_at = now();
            }
            $blog->save();
            $status = true;
            $msgClass = 'alert-success';
            $message = 'Blog has been removed successfully!';
            $json['redirectURL'] = url('admin/blogs');
        }
        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }

    public function galleryImages($blogID) {
        $blog = $this->blogModel::findOrFail($blogID);
        $data = [];
        $data['page_title'] = 'Blog Gallery — ' . $blog->title;
        $data['pageType'] = 'gallery';
        $data['leftMenuActive'] = 'blogs';
        $data['blogInfo'] = $blog;
        $data['blogId'] = $blog->id;
        $data['mediaItems'] = $blog->media()->latest()->get();
        return view('admin/blogs/blogGalleryImages', $data);
    }

    public function getGalleryMediaList($blogID) {
        $blog = $this->blogModel::findOrFail($blogID);
        $data['mediaItems'] = $blog->media()->latest()->get();
        $html = view('admin.blogs.getBlogGalleryList', $data)->render();
        return response()->json(['html' => $html]);
    }

    public function uploadGalleryMedia(Request $request, $blogID) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please try again later.';

        $blog = $this->blogModel::findOrFail($blogID);
        $maxKb = (int) config('constants.BLOG_GALLERY_MAX_FILE_KB', 51200);

        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:' . $maxKb,
        ]);

        if ($validator->fails()) {
            $message = 'Please upload valid image or video files.';
            $json['errors'] = $validator->errors();
            $json['success'] = false;
            $json['messageClass'] = $msgClass;
            $json['message'] = $message;
            return response()->json($json);
        }

        $destinationPath = public_path(BlogMedia::galleryBasePath());

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $uploaded = 0;
        $rejected = [];

        try {
            foreach ($request->file('files') as $file) {
                if (!$this->isAllowedGalleryFile($file)) {
                    $rejected[] = $file->getClientOriginalName();
                    continue;
                }

                $originalName = $file->getClientOriginalName();
                $mime = $file->getMimeType();
                $size = $file->getSize();
                $storedName = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $storedName);

                BlogMedia::create([
                    'blog_id' => $blog->id,
                    'file_name' => $originalName,
                    'disk' => 'public',
                    'path' => $storedName,
                    'mime' => $mime,
                    'size' => $size,
                ]);
                $uploaded++;
            }

            if ($uploaded > 0) {
                $status = true;
                $msgClass = 'alert-success';
                $message = $uploaded . ' file(s) uploaded successfully.';
                if (count($rejected) > 0) {
                    $message .= ' Skipped: ' . implode(', ', $rejected);
                }
            } else {
                $message = 'No valid files uploaded. Allowed: images (jpg, png, webp, gif) and videos (mp4, webm, ogg, mov).';
            }
        } catch (\Throwable $e) {
            Log::error('Blog Gallery Upload Error: ' . $e->getMessage());
        }

        $data['mediaItems'] = $blog->media()->latest()->get();
        $json['html'] = view('admin.blogs.getBlogGalleryList', $data)->render();
        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }

    public function deleteGalleryMedia(Request $request) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please try again later.';
        $id = $request->record_id ?? '';

        try {
            $media = BlogMedia::findOrFail($id);
            $filePath = $media->fullDiskPath();
            if (is_file($filePath)) {
                unlink($filePath);
            }
            $media->delete();
            $status = true;
            $msgClass = 'alert-success';
            $message = 'Media removed successfully.';
        } catch (\Throwable $e) {
            Log::error('Blog Gallery Delete Error: ' . $e->getMessage());
        }

        $json['success'] = $status;
        $json['messageClass'] = $msgClass;
        $json['message'] = $message;
        return response()->json($json);
    }

    protected function isAllowedGalleryFile($file)
    {
        $mime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        $imageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $videoMimes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
        $imageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $videoExt = ['mp4', 'webm', 'ogg', 'mov'];

        if (in_array($mime, $imageMimes, true) && in_array($extension, $imageExt, true)) {
            return true;
        }

        if (in_array($mime, $videoMimes, true) && in_array($extension, $videoExt, true)) {
            return true;
        }

        return false;
    }
}
