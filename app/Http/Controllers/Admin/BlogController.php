<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Admin\BlogCategory;
use App\Models\Admin\BlogMedia;
use App\Models\Admin\Blogs;
use App\Models\Media;
use App\Services\MediaService;

class BlogController extends Controller {

    protected $categoryModel;
    protected $blogModel;

    public function __construct() {
        $this->categoryModel = new BlogCategory();
        $this->blogModel = new Blogs();
    }

    public function index(Request $request) {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $query = $this->blogModel::query()->with('categories')->where('is_deleted', 'no')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.addcslashes(trim($request->input('search')), '%_').'%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('sub_title', 'like', $term)->orWhere('slug', 'like', $term));
            });

        return view('admin/blogs/blogsList', [
            'blogs' => $this->paginateRequest($request, $query->latest()),
            'page_title' => 'Blogs', 'pageType' => 'list', 'leftMenuActive' => 'blogs',
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
            'category_ids' => ['required', 'integer', Rule::exists('blog_categories', 'id')->where('status', 'active')->where('is_deleted', 'no')],
            'title' => ['required', 'string', 'max:255'],
            'sub_title' => ['required', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blogs', 'slug')->ignore($request->filled('id') ? $request->id : null),
            ],
            'meta_description' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($validator->fails()) {
            $message = 'Please enter the valid data.';
            $json['errors'] = $validator->errors();
            $json['success'] = false;
            $json['messageClass'] = $msgClass;
            $json['message'] = $message;
            return response()->json($json);
        }

        if ($request->input('status') === 'active') {
            abort_unless($request->user()->hasPermission('articles.publish'), 403, 'You do not have permission to publish articles.');
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
            $blogModel->publication_status = $request->status === 'active' ? 'published' : 'inactive';
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
            'category_ids' => ['required', 'integer', Rule::exists('blog_categories', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'sub_title' => ['required', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blogs', 'slug')->ignore($request->filled('id') ? $request->id : null),
            ],
            'meta_description' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($validator->fails()) {
            $message = 'Please enter the valid data.';
            $json['errors'] = $validator->errors();
            $json['success'] = false;
            $json['messageClass'] = $msgClass;
            $json['message'] = $message;
            return response()->json($json);
        }

        $blog = $this->blogModel::findOrFail($request->id);
        $category = $this->categoryModel::findOrFail($request->input('category_ids'));
        abort_unless(
            ($category->status === 'active' && $category->is_deleted === 'no') || $blog->categories()->whereKey($category->id)->exists(),
            422,
            'Only active categories may be newly assigned.'
        );
        if ($request->input('status') !== $blog->status && ($request->input('status') === 'active' || $blog->status === 'active')) {
            abort_unless($request->user()->hasPermission('articles.publish'), 403, 'You do not have permission to publish or unpublish articles.');
        }

        try {
            $blog->title = $request->title;
            $blog->sub_title = $request->sub_title;
            $blog->meta_title = $request->meta_title;
            $blog->slug = $request->slug;
            $blog->meta_description = $request->meta_description;
            $blog->description = $request->description;
            $blog->status = $request->status;
            $blog->publication_status = $request->status === 'active' ? 'published' : 'inactive';

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

    public function uploadGalleryMedia(Request $request, $blogID, MediaService $mediaService) {
        $json = [];
        $status = false;
        $msgClass = 'alert-danger';
        $message = 'Something went Wrong! Please try again later.';

        $blog = $this->blogModel::findOrFail($blogID);
        $maxKb = (int) config('media.max_video_kb', 51200);

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
                try {
                    if (str_starts_with((string) $mime, 'image/')) {
                        $mediaService->store($file, (int) $request->user()->id, null, 'media', 'files', (int) $blog->id);
                        $uploaded++;
                        continue;
                    }

                    $extension = strtolower($file->getClientOriginalExtension());
                    $storedName = Str::uuid()->toString().'.'.$extension;
                    $file->move($destinationPath, $storedName);
                    try {
                        BlogMedia::create([
                            'blog_id' => $blog->id,
                            'file_name' => $originalName,
                            'disk' => 'public',
                            'path' => $storedName,
                            'mime' => $mime,
                            'size' => $size,
                        ]);
                        $uploaded++;
                    } catch (\Throwable $exception) {
                        @unlink($destinationPath.DIRECTORY_SEPARATOR.$storedName);
                        throw $exception;
                    }
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $rejected[] = $originalName;
                } catch (\Throwable $exception) {
                    Log::error('Blog gallery file could not be stored.', ['filename' => $originalName]);
                    $rejected[] = $originalName;
                }
            }

            if ($uploaded > 0) {
                $status = true;
                $msgClass = 'alert-success';
                $message = $uploaded . ' file(s) uploaded successfully.';
                if (count($rejected) > 0) {
                    $message .= ' Skipped: ' . implode(', ', $rejected);
                }
            } else {
                $message = 'No valid files uploaded. Allowed: images (jpg, jpeg, png, gif, webp, avif where supported) and videos (mp4, webm, ogg, mov).';
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
            if ($media->disk === 'media' && $media->isImage()) {
                app(MediaService::class)->deleteGalleryItem(Media::findOrFail($media->id));
                $status = true;
                $msgClass = 'alert-success';
                $message = 'Media removed successfully.';
                $json['success'] = $status;
                $json['messageClass'] = $msgClass;
                $json['message'] = $message;
                return response()->json($json);
            }
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

        $imageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
        $videoMimes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
        $imageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
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
