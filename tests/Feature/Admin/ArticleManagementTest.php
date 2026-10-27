<?php

namespace Tests\Feature\Admin;

use App\Models\Admin\Blogs as Article;
use App\Models\Admin\Tag;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_crud_relationships_search_filters_and_public_visibility(): void
    {
        $admin = $this->adminWithPermissions('articles.view', 'articles.create', 'articles.update', 'articles.delete', 'articles.publish');
        [$category, $tagA, $tagB, $author] = $this->references($admin);
        $payload = $this->payload($category, $tagA, $admin, ['title' => 'Best Electric Cars in India', 'tags' => [$tagA->id, $tagB->id], 'status' => 'published']);

        $created = $this->actingAs($admin)->postJson('/api/admin/articles', $payload)->assertCreated()
            ->assertJsonPath('data.slug', 'best-electric-cars-in-india')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.author.id', $author->id)
            ->assertJsonPath('data.status', 'published');
        $article = Article::whereSlug('best-electric-cars-in-india')->firstOrFail();
        $this->assertEqualsCanonicalizing([$tagA->id, $tagB->id], $article->tags()->pluck('tags.id')->all());

        $this->actingAs($admin)->getJson('/api/admin/articles?search=Electric&category_id='.$category->id.'&tag_id='.$tagA->id.'&status=published')
            ->assertOk()->assertJsonPath('data.total', 1)->assertJsonMissingPath('data.data.0.password');
        $this->actingAs($admin)->getJson('/api/admin/articles/'.$article->id)->assertOk()->assertJsonPath('data.content', 'Plain text content');
        $this->getJson('/api/articles/'.$article->slug)->assertOk()->assertJsonPath('data.title', 'Best Electric Cars in India');

        $updated = $this->payload($category, $tagB, $admin, ['title' => 'Updated EV Guide', 'slug' => 'updated-ev-guide', 'status' => 'published', 'tags' => [$tagA->id]]);
        $publishedAt = $article->published_at->toDateTimeString();
        $this->actingAs($admin)->putJson('/api/admin/articles/'.$article->id, $updated)->assertOk()->assertJsonPath('data.slug', 'updated-ev-guide');
        $this->assertEquals($publishedAt, $article->fresh()->published_at->toDateTimeString());
        $this->assertSame([$tagA->id], $article->fresh()->tags()->pluck('tags.id')->all());
        $noTagField = array_diff_key($updated, ['tags' => true]);
        $noTagField['title'] = 'Updated EV Guide revised';
        $noTagField['slug'] = 'updated-ev-guide-revised';
        $tagA->update(['status' => 'inactive']);
        $category->delete();
        $this->actingAs($admin)->putJson('/api/admin/articles/'.$article->id, $noTagField)->assertOk();
        $this->assertSame([$tagA->id], $article->fresh()->tags()->pluck('tags.id')->all());
        $this->assertSame($category->id, $article->fresh()->category_id);
        $this->actingAs($admin)->patchJson('/api/admin/articles/'.$article->id.'/status', ['status' => 'draft'])->assertOk();
        $this->getJson('/api/articles/'.$article->slug)->assertNotFound();
        $this->actingAs($admin)->deleteJson('/api/admin/articles/'.$article->id)->assertNoContent();
        $this->assertSoftDeleted('blogs', ['id' => $article->id]);
        $this->assertDatabaseMissing('blog_tag', ['blog_id' => $article->id]);
    }

    public function test_article_validation_and_publish_permission_are_enforced(): void
    {
        $admin = $this->adminWithPermissions('articles.view', 'articles.create', 'articles.update');
        [$category, $tag, , $author] = $this->references($admin);
        $base = $this->payload($category, $tag, $author);

        $this->actingAs($admin)->postJson('/api/admin/articles', [])->assertUnprocessable()->assertJsonValidationErrors(['title', 'content', 'category_id', 'author_id']);
        $this->actingAs($admin)->postJson('/api/admin/articles', array_merge($base, ['title' => '   ']))->assertUnprocessable()->assertJsonValidationErrors(['title']);
        $this->actingAs($admin)->postJson('/api/admin/articles', array_merge($base, ['category_id' => 99999]))->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
        $this->actingAs($admin)->postJson('/api/admin/articles', array_merge($base, ['author_id' => 99999]))->assertUnprocessable()->assertJsonValidationErrors(['author_id']);
        $this->actingAs($admin)->postJson('/api/admin/articles', array_merge($base, ['status' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors(['status']);
        $this->actingAs($admin)->postJson('/api/admin/articles', array_merge($base, ['slug' => 'already-used']))->assertCreated();
        $this->actingAs($admin)->postJson('/api/admin/articles', array_merge($base, ['title' => 'Different title', 'slug' => 'already-used']))->assertUnprocessable()->assertJsonValidationErrors(['slug']);
        $this->actingAs($admin)->postJson('/api/admin/articles', array_merge($base, ['status' => 'published']))->assertForbidden();
    }

    public function test_featured_image_upload_persists_replaces_clears_and_rejects_invalid_files(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('articles.view', 'articles.create', 'articles.update', 'articles.publish');
        [$category, $tag] = $this->references($admin);

        $validImages = [
            'jpg' => file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg')),
            'jpeg' => file_get_contents(base_path('public/assets/media/blogs/1779039811-6a09fe435ed13.jpg')),
            'png' => file_get_contents(base_path('public/assets/images/avatars/1.png')),
            'webp' => base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA=='),
        ];
        foreach ($validImages as $extension => $contents) {
            $image = UploadedFile::fake()->createWithContent('cover.'.$extension, $contents);
            $response = $this->actingAs($admin)->post('/api/admin/articles', array_merge($this->payload($category, $tag, $admin, ['title' => 'Image '.$extension, 'slug' => 'image-'.$extension]), ['featured_image' => $image]), ['Accept' => 'application/json']);
            $response->assertCreated();
            $article = Article::whereSlug('image-'.$extension)->firstOrFail();
            $this->assertSame(Storage::disk('brand_media')->url($article->featured_image), $response->json('data.featured_image'));
            $this->assertNotNull($response->json('data.featured_image'));
            Storage::disk('media')->assertExists($article->featured_image);
        }

        $jpegBytes = file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg'));
        $nearLimitJpeg = str_pad($jpegBytes, 5100 * 1024, "\0");
        $nearLimit = $this->actingAs($admin)->post('/api/admin/articles', array_merge($this->payload($category, $tag, $admin, ['title' => 'Near limit image', 'slug' => 'near-limit-image']), ['featured_image' => UploadedFile::fake()->createWithContent('near-limit.jpg', $nearLimitJpeg)]), ['Accept' => 'application/json']);
        $nearLimit->assertCreated();

        $invalid = $this->actingAs($admin)->post('/api/admin/articles', array_merge($this->payload($category, $tag, $admin, ['title' => 'Invalid image']), ['featured_image' => UploadedFile::fake()->create('payload.jpg', 1, 'text/plain')]), ['Accept' => 'application/json']);
        $invalid->assertUnprocessable()->assertJsonValidationErrors(['featured_image']);
        $oversizedJpeg = str_pad($jpegBytes, 5121 * 1024, "\0");
        $large = $this->actingAs($admin)->post('/api/admin/articles', array_merge($this->payload($category, $tag, $admin, ['title' => 'Large image']), ['featured_image' => UploadedFile::fake()->createWithContent('large.jpg', $oversizedJpeg)]), ['Accept' => 'application/json']);
        $large->assertUnprocessable()->assertJsonValidationErrors(['featured_image']);

        $article = Article::whereSlug('image-jpg')->firstOrFail();
        $oldPath = $article->featured_image;
        $replace = UploadedFile::fake()->createWithContent('replacement.png', file_get_contents(base_path('public/assets/images/avatars/1.png')));
        $this->actingAs($admin)->post('/api/admin/articles/'.$article->id, array_merge($this->payload($category, $tag, $admin, ['title' => $article->title, 'slug' => $article->slug, 'status' => 'published']), ['_method' => 'PUT', 'featured_image' => $replace]), ['Accept' => 'application/json'])->assertOk();
        $article->refresh();
        $this->assertNotSame($oldPath, $article->featured_image);
        Storage::disk('media')->assertExists($article->featured_image);
        Storage::disk('media')->assertMissing($oldPath);

        $this->actingAs($admin)->putJson('/api/admin/articles/'.$article->id, array_merge($this->payload($category, $tag, $admin, ['title' => $article->title, 'slug' => $article->slug, 'status' => 'published']), ['clear_featured_image' => true]))->assertOk()->assertJsonPath('data.featured_image', null);
        Storage::disk('media')->assertMissing($article->featured_image);
    }

    public function test_article_views_and_pagination_render(): void
    {
        $admin = $this->adminWithPermissions('articles.view', 'articles.create', 'articles.update');
        [$category, $tag] = $this->references($admin);
        $this->actingAs($admin)->postJson('/api/admin/articles', $this->payload($category, $tag, $admin))->assertCreated();
        $article = Article::firstOrFail();
        foreach (range(1, 31) as $number) {
            Article::create([
                'user_id' => $admin->id,
                'category_id' => $category->id,
                'title' => 'Pagination guide '.$number,
                'slug' => 'pagination-guide-'.$number,
                'excerpt' => 'Pagination search result',
                'description' => 'Plain text content',
                'status' => 'inactive',
                'publication_status' => 'draft',
                'is_deleted' => 'no',
            ]);
        }

        $this->actingAs($admin)->get('/admin/articles')->assertOk()->assertSee('Articles')->assertSee('Pagination guide 31');
        $this->actingAs($admin)->getJson('/api/admin/articles?per_page=10')->assertOk()->assertJsonPath('data.current_page', 1)->assertJsonPath('data.last_page', 4);
        $this->actingAs($admin)->getJson('/api/admin/articles?page=2&per_page=10')->assertOk()->assertJsonPath('data.current_page', 2);
        $this->actingAs($admin)->getJson('/api/admin/articles?page=4&per_page=10')->assertOk()->assertJsonPath('data.current_page', 4)->assertJsonPath('data.to', 32);
        $this->actingAs($admin)->getJson('/api/admin/articles?search=Pagination&per_page=10')->assertOk()->assertJsonPath('data.current_page', 1)->assertJsonPath('data.total', 31);
        $this->actingAs($admin)->get('/admin/articles/create')->assertOk()->assertSee('Create article');
        $this->actingAs($admin)->get('/admin/articles/'.$article->id)->assertOk()->assertSee($article->title);
        $this->actingAs($admin)->get('/admin/articles/'.$article->id.'/edit')->assertOk()->assertSee('Edit article');
    }

    public function test_article_seeder_is_idempotent_and_reuses_existing_records(): void
    {
        $admin = $this->adminWithPermissions('articles.create');
        $this->references($admin);

        Artisan::call('db:seed', ['--class' => ArticleSeeder::class, '--force' => true]);
        $article = Article::whereSlug('how-to-improve-your-car-mileage')->firstOrFail();
        $publishedAt = $article->published_at->toDateTimeString();
        Artisan::call('db:seed', ['--class' => ArticleSeeder::class, '--force' => true]);

        $this->assertSame(2, Article::whereIn('slug', ['how-to-improve-your-car-mileage', 'things-to-check-before-buying-a-used-car'])->count());
        $this->assertSame($publishedAt, $article->fresh()->published_at->toDateTimeString());
        $this->assertSame($admin->id, $article->fresh()->user_id);
    }

    public function test_featured_image_uses_public_media_storage_and_survives_a_fresh_read(): void
    {
        $admin = $this->adminWithPermissions('articles.view', 'articles.create', 'articles.delete', 'articles.publish');
        [$category, $tag] = $this->references($admin);
        $response = $this->actingAs($admin)->post('/api/admin/articles', array_merge(
            $this->payload($category, $tag, $admin, ['title' => 'Stored image article', 'status' => 'published']),
            ['featured_image' => UploadedFile::fake()->createWithContent('cover.jpg', file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg')))]
        ), ['Accept' => 'application/json'])->assertCreated();

        $article = Article::whereSlug('stored-image-article')->firstOrFail();
        $publicPath = public_path('assets/media/'.$article->featured_image);
        try {
            $this->assertFileExists($publicPath);
            $url = $response->json('data.featured_image');
            $this->assertSame(Storage::disk('brand_media')->url($article->featured_image), $url);
            $this->actingAs($admin)->getJson('/api/admin/articles/'.$article->id)->assertOk()->assertJsonPath('data.featured_image', $url);
            $this->actingAs($admin)->deleteJson('/api/admin/articles/'.$article->id)->assertNoContent();
            $this->assertFileDoesNotExist($publicPath);
        } finally {
            if (is_file($publicPath)) unlink($publicPath);
        }
    }

    private function references(User $author): array
    {
        $category = Category::create(['name' => 'Guides '.uniqid(), 'slug' => 'guides-'.uniqid(), 'category_type' => 'automotive', 'status' => 'active']);
        $tagA = Tag::create(['name' => 'Electric '.uniqid(), 'slug' => 'electric-'.uniqid(), 'status' => 'active']);
        $tagB = Tag::create(['name' => 'Buying '.uniqid(), 'slug' => 'buying-'.uniqid(), 'status' => 'active']);
        return [$category, $tagA, $tagB, $author];
    }

    private function payload(Category $category, Tag $tag, User $author, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Electric car guide',
            'content' => 'Plain text content',
            'category_id' => $category->id,
            'tags' => [$tag->id],
            'author_id' => $author->id,
            'status' => 'draft',
        ], $overrides);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $role = Role::create(['name' => 'Article tester '.uniqid(), 'slug' => 'article-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Articles']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);
        return $admin;
    }
}
