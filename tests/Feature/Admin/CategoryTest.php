<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_crud_and_active_lookup_work(): void
    {
        $admin = $this->adminWithPermissions('categories.view', 'categories.create', 'categories.update', 'categories.delete');

        $created = $this->actingAs($admin)->postJson('/api/admin/categories', [
            'name' => '  Electric Cars  ',
            'description' => 'Battery-powered vehicles.',
            'category_type' => 'automotive',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $created->assertCreated()->assertJsonPath('data.name', 'Electric Cars')->assertJsonPath('data.slug', 'electric-cars');
        $category = Category::firstOrFail();
        $this->assertNull($category->image);

        $this->actingAs($admin)->getJson('/api/admin/categories/'.$category->id)->assertOk()->assertJsonPath('data.children_count', 0);
        $this->actingAs($admin)->putJson('/api/admin/categories/'.$category->id, ['name' => 'EV Cars', 'category_type' => 'automotive', 'status' => 'inactive'])->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'EV Cars', 'slug' => 'ev-cars', 'status' => 'inactive']);

        $this->actingAs($admin)->getJson('/api/admin/categories/active')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($admin)->deleteJson('/api/admin/categories/'.$category->id)->assertNoContent();
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_permissions_are_enforced(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/admin/categories')->assertForbidden();
        $this->actingAs($admin)->postJson('/api/admin/categories', $this->categoryData())->assertForbidden();

        $viewer = $this->adminWithPermissions('categories.view');
        $this->actingAs($viewer)->getJson('/api/admin/categories')->assertOk();
        $this->actingAs($viewer)->postJson('/api/admin/categories', $this->categoryData())->assertForbidden();
    }

    public function test_hierarchy_validation_and_delete_protection_work(): void
    {
        $admin = $this->adminWithPermissions('categories.view', 'categories.create', 'categories.update', 'categories.delete');
        $parent = Category::create($this->categoryData(['name' => 'Cars', 'slug' => 'cars']));
        $child = Category::create($this->categoryData(['name' => 'SUVs', 'slug' => 'suvs', 'parent_id' => $parent->id]));
        $inactive = Category::create($this->categoryData(['name' => 'Inactive Parent', 'slug' => 'inactive-parent', 'status' => 'inactive']));

        $this->actingAs($admin)->putJson('/api/admin/categories/'.$parent->id, ['name' => 'Cars', 'category_type' => 'automotive', 'status' => 'active', 'parent_id' => $child->id])->assertStatus(422);
        $this->actingAs($admin)->postJson('/api/admin/categories', $this->categoryData(['name' => 'Bad Child', 'slug' => null, 'parent_id' => $inactive->id]))->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
        $this->actingAs($admin)->deleteJson('/api/admin/categories/'.$parent->id)->assertStatus(409);
        $this->actingAs($admin)->deleteJson('/api/admin/categories/'.$child->id)->assertNoContent();
    }

    public function test_search_filters_and_shared_pagination_metadata_work(): void
    {
        $admin = $this->adminWithPermissions('categories.view', 'categories.create');
        foreach (range(1, 16) as $number) {
            Category::create($this->categoryData(['name' => 'Category '.$number, 'slug' => 'category-'.$number, 'status' => $number === 1 ? 'inactive' : 'active', 'category_type' => $number % 2 ? 'automotive' : 'content']));
        }

        $this->actingAs($admin)->getJson('/api/admin/categories?search=Category%201&status=inactive')->assertOk()->assertJsonPath('data.total', 1);
        $this->actingAs($admin)->getJson('/api/admin/categories?category_type=content')->assertOk()->assertJsonPath('data.total', 8);
        $this->actingAs($admin)->getJson('/api/admin/categories?page=2&per_page=10')->assertOk()->assertJsonPath('data.current_page', 2)->assertJsonPath('data.per_page', 10)->assertJsonPath('data.from', 11)->assertJsonPath('data.to', 16);
    }

    public function test_category_image_is_optional_and_unchanged_when_updating_other_fields(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('categories.create', 'categories.view', 'categories.update');
        $png = $this->validPng();

        $response = $this->actingAs($admin)->post('/api/admin/categories', array_merge($this->categoryData(['name' => 'SUV']), [
            'image' => UploadedFile::fake()->createWithContent('suv.png', $png),
        ]), ['Accept' => 'application/json'])->assertCreated();

        $category = Category::findOrFail($response->json('data.id'));
        $imagePath = $category->image;
        $this->assertNotEmpty($imagePath);
        Storage::disk('media')->assertExists($imagePath);

        $this->actingAs($admin)->putJson('/api/admin/categories/'.$category->id, [
            'name' => 'Sport Utility Vehicle', 'category_type' => 'automotive', 'status' => 'active',
        ])->assertOk();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Sport Utility Vehicle', 'image' => $imagePath]);
        Storage::disk('media')->assertExists($imagePath);
    }

    public function test_category_image_can_be_replaced_and_removed_without_orphaning_old_media(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('categories.create', 'categories.update');
        $png = $this->validPng();
        $created = $this->actingAs($admin)->post('/api/admin/categories', array_merge($this->categoryData(['name' => 'Sedan']), [
            'image' => UploadedFile::fake()->createWithContent('sedan.png', $png),
        ]), ['Accept' => 'application/json'])->assertCreated();
        $category = Category::findOrFail($created->json('data.id'));
        $oldPath = $category->image;
        $oldMedia = Media::where('disk', 'media')->where('path', $oldPath)->firstOrFail();

        $jpeg = file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg'));
        $this->actingAs($admin)->post('/api/admin/categories/'.$category->id, array_merge($this->categoryData([
            'name' => 'Sedan', 'slug' => null,
        ]), ['_method' => 'PUT', 'image' => UploadedFile::fake()->createWithContent('sedan-new.jpg', $jpeg)]), ['Accept' => 'application/json'])->assertOk();

        $newPath = $category->fresh()->image;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('media')->assertMissing($oldPath);
        Storage::disk('media')->assertExists($newPath);
        $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);

        $this->actingAs($admin)->putJson('/api/admin/categories/'.$category->id, [
            'name' => 'Sedan', 'category_type' => 'automotive', 'status' => 'active', 'remove_image' => true,
        ])->assertOk();
        $this->assertNull($category->fresh()->image);
        Storage::disk('media')->assertMissing($newPath);
    }

    public function test_category_image_rejects_invalid_and_oversized_uploads(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('categories.create');
        $payload = $this->categoryData(['name' => 'Invalid Image Category']);

        $this->actingAs($admin)->post('/api/admin/categories', array_merge($payload, [
            'image' => UploadedFile::fake()->createWithContent('not-an-image.png', 'not image content'),
        ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['image']);

        $jpeg = file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg'));
        $maxKb = (int) config('media.max_image_kb', 5120);
        $oversized = UploadedFile::fake()->createWithContent('oversized.jpg', str_pad($jpeg, ($maxKb + 1) * 1024, "\0"));
        $this->actingAs($admin)->post('/api/admin/categories', array_merge($payload, [
            'name' => 'Oversized Image Category', 'image' => $oversized,
        ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['image']);

        $this->assertDatabaseMissing('categories', ['name' => 'Invalid Image Category']);
        $this->assertDatabaseMissing('categories', ['name' => 'Oversized Image Category']);
    }

    public function test_category_forms_show_optional_upload_and_existing_image_preview(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('categories.create', 'categories.view', 'categories.update');
        $imagePath = 'images/2026/10/category.png';
        Storage::disk('media')->put($imagePath, $this->validPng());
        $category = Category::create($this->categoryData(['name' => 'Hatchback', 'image' => $imagePath]));

        $this->actingAs($admin)->get('/admin/categories/create')->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image"', false);

        $this->actingAs($admin)->get('/admin/categories/'.$category->id.'/edit')->assertOk()
            ->assertSee('Current image')
            ->assertSee('Remove current image')
            ->assertSee(Storage::disk('media')->url($imagePath), false);
    }

    public function test_replacing_one_categories_shared_image_preserves_the_other_reference(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('categories.update');
        $sharedPath = 'images/2026/10/shared-category.png';
        Storage::disk('media')->put($sharedPath, $this->validPng());
        $media = Media::create([
            'file_name' => 'shared-category.png', 'original_name' => 'shared-category.png',
            'disk' => 'media', 'path' => $sharedPath, 'mime' => 'image/png', 'extension' => 'png',
            'size' => 68, 'width' => 1, 'height' => 1, 'created_by' => $admin->id,
        ]);
        $category = Category::create($this->categoryData(['name' => 'SUV', 'image' => $sharedPath]));
        Category::create($this->categoryData(['name' => 'Crossover', 'slug' => 'crossover', 'image' => $sharedPath]));

        $this->actingAs($admin)->post('/api/admin/categories/'.$category->id, array_merge($this->categoryData([
            'name' => 'SUV', 'slug' => null,
        ]), ['_method' => 'PUT', 'image' => UploadedFile::fake()->createWithContent('new-suv.png', $this->validPng())]), ['Accept' => 'application/json'])->assertOk();

        Storage::disk('media')->assertExists($sharedPath);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'path' => $sharedPath]);
        $this->assertDatabaseHas('categories', ['name' => 'Crossover', 'image' => $sharedPath]);
    }

    private function validPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Category tester '.uniqid(), 'slug' => 'category-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Categories']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);

        return $admin;
    }

    private function categoryData(array $overrides = []): array
    {
        return array_merge(['name' => 'Maruti', 'slug' => 'maruti', 'description' => 'Category description.', 'category_type' => 'automotive', 'status' => 'active', 'sort_order' => 1], $overrides);
    }
}