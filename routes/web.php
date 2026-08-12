<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as DashboardController;
use App\Http\Controllers\Admin\BlogCategoryController as BlogCategoryController;
use App\Http\Controllers\Admin\BlogController as BlogController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    // Admin Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Blog Categories Routes Start
    // List all categories
    Route::get('blog-categories', [BlogCategoryController::class, 'index'])->name('blog-categories.index');
    Route::get('get-blog-categories', [BlogCategoryController::class, 'getCategoriesList']);
    // Show add form
    Route::get('blog-categories/create', [BlogCategoryController::class, 'create'])->name('blog-categories.create');
    // Handle add form submission
    Route::post('blog-categories/store', [BlogCategoryController::class, 'store'])->name('blog-categories.store');
    // Show edit form
    Route::get('blog-categories/edit/{id}', [BlogCategoryController::class, 'edit'])->name('blog-categories.edit');
    // Handle update form submission
    Route::post('blog-categories/update', [BlogCategoryController::class, 'update'])->name('blog-categories.update');
    Route::post('blog-categories/delete', [BlogCategoryController::class, 'destroy'])->name('blog-categories.delete');
    // Blog Categories Routes end

    // Blogs Routes Start
    Route::get('blogs', [BlogController::class, 'index']);
    Route::get('get-blogs', [BlogController::class, 'getBlogsList']);
    Route::get('blog/create', [BlogController::class, 'create']);
    Route::post('blog/store', [BlogController::class, 'store'])->name('blog.store');
    Route::get('blog/edit/{id}', [BlogController::class, 'edit'])->name('blog.edit');
    Route::post('blog/update', [BlogController::class, 'update'])->name('blog.update');
    Route::post('blog/delete', [BlogController::class, 'destroy'])->name('blog.delete');
    Route::get('blog/gallery/{id}', [BlogController::class, 'galleryImages'])->name('blog.gallery');
    Route::get('blog/gallery/{id}/media', [BlogController::class, 'getGalleryMediaList']);
    Route::post('blog/gallery/{id}/upload', [BlogController::class, 'uploadGalleryMedia'])->name('blog.gallery.upload');
    Route::post('blog/gallery/media/delete', [BlogController::class, 'deleteGalleryMedia'])->name('blog.gallery.delete');
});


require __DIR__.'/auth.php';
