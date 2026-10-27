<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Media;
use App\Models\HomepageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_list_metadata_and_delete_use_central_media_storage(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('media.view', 'media.upload', 'media.update', 'media.delete');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $response = $this->actingAs($admin)->post('/api/admin/media', ['file' => UploadedFile::fake()->createWithContent('car.png', $png)], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('data.original_name', 'car.png')->assertJsonPath('data.mime_type', 'image/png');
        $id = $response->json('data.id');
        $path = Media::findOrFail($id)->path;
        Storage::disk('media')->assertExists($path);
        $this->assertStringContainsString('/assets/media/'.$path, $response->json('data.url'));
        $this->actingAs($admin)->patchJson('/api/admin/media/'.$id, ['alt_text' => 'Car', 'title' => 'Test'])->assertOk()->assertJsonPath('data.alt_text', 'Car');
        $this->actingAs($admin)->getJson('/api/admin/media?search=car&format=png')->assertOk()->assertJsonPath('data.data.0.id', $id);
        $jpeg = file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg'));
        $this->actingAs($admin)->post('/api/admin/media/'.$id.'/replace', ['file' => UploadedFile::fake()->createWithContent('replacement.jpg', $jpeg)], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.extension', 'jpg');
        $newPath = Media::findOrFail($id)->path;
        $this->assertNotSame($path, $newPath);
        Storage::disk('media')->assertMissing($path);
        Storage::disk('media')->assertExists($newPath);
        $this->actingAs($admin)->deleteJson('/api/admin/media/'.$id)->assertNoContent();
        Storage::disk('media')->assertMissing($newPath);
    }

    public function test_invalid_file_and_missing_permissions_are_rejected(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('media.upload');
        $this->actingAs($admin)->post('/api/admin/media', ['file' => UploadedFile::fake()->createWithContent('bad.txt', 'not an image')], ['Accept' => 'application/json'])->assertUnprocessable();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $this->actingAs($admin)->post('/api/admin/media', ['file' => UploadedFile::fake()->createWithContent('fake.jpg', $png)], ['Accept' => 'application/json'])->assertUnprocessable();
        $jpeg = file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg'));
        $oversized = UploadedFile::fake()->createWithContent('oversized.jpg', str_pad($jpeg, 5121 * 1024, "\0"));
        $this->actingAs($admin)->post('/api/admin/media', ['file' => $oversized], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->actingAs($admin)->getJson('/api/admin/media')->assertForbidden();
    }

    public function test_supported_raster_formats_are_validated_and_recorded(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('media.upload');
        $fixtures = [
            'jpg' => file_get_contents(base_path('public/assets/media/blogs/1779039768-6a09fe18eab15.jpg')),
            'jpeg' => file_get_contents(base_path('public/assets/media/blogs/1779039811-6a09fe435ed13.jpg')),
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            'gif' => base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='),
            'webp' => base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA=='),
        ];
        foreach ($fixtures as $extension => $contents) {
            $expectedMime = $extension === 'jpg' || $extension === 'jpeg' ? 'image/jpeg' : 'image/'.$extension;
            $response = $this->actingAs($admin)->post('/api/admin/media', [
                'file' => UploadedFile::fake()->createWithContent('sample.'.$extension, $contents),
            ], ['Accept' => 'application/json'])->assertCreated();
            $this->assertSame($expectedMime, $response->json('data.mime_type'));
            $this->assertStringContainsString('/assets/media/', $response->json('data.url'));
            $this->assertNotEmpty($response->json('data.width'));
            $this->assertNotEmpty($response->json('data.height'));
        }
    }

    public function test_avif_upload_works_when_the_runtime_can_inspect_avif(): void
    {
        $path = base_path('public/assets/media/cars/car-thumbnails/qCskF1GwL8tSmT3isX44WHrdqPcjgzQC6BdDUBe9.avif');
        $image = @getimagesize($path);
        if (! $image || ($image['mime'] ?? null) !== 'image/avif') {
            $this->markTestSkipped('This PHP build cannot inspect AVIF image data.');
        }
        Storage::fake('media');
        $admin = $this->adminWithPermissions('media.upload');
        $response = $this->actingAs($admin)->post('/api/admin/media', [
            'file' => new UploadedFile($path, 'runtime-check.avif', 'image/avif', null, true),
        ], ['Accept' => 'application/json'])->assertCreated();
        $this->assertSame('image/avif', $response->json('data.mime_type'));
        $this->assertNotEmpty($response->json('data.url'));
    }

    public function test_search_and_format_filters_preserve_shared_pagination(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('media.view', 'media.upload');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        foreach (range(1, 16) as $number) {
            $this->actingAs($admin)->post('/api/admin/media', [
                'file' => UploadedFile::fake()->createWithContent('gallery-'.$number.'.png', $png),
            ], ['Accept' => 'application/json'])->assertCreated();
        }
        $response = $this->actingAs($admin)->getJson('/api/admin/media?search=gallery&format=png&per_page=10&page=2')->assertOk();
        $response->assertJsonPath('data.total', 16)->assertJsonPath('data.current_page', 2)->assertJsonCount(6, 'data.data');
    }

    public function test_existing_media_can_be_selected_by_a_brand_and_cannot_be_deleted_while_referenced(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('media.view', 'media.upload', 'media.delete', 'brands.create');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $uploaded = $this->actingAs($admin)->post('/api/admin/media', [
            'file' => UploadedFile::fake()->createWithContent('shared.png', $png),
        ], ['Accept' => 'application/json'])->assertCreated();
        $mediaId = $uploaded->json('data.id');
        $mediaPath = \App\Models\Media::findOrFail($mediaId)->path;

        $this->actingAs($admin)->post('/api/admin/brands', [
            'name' => 'Shared Media Motors', 'status' => 'active', 'logo_media_id' => $mediaId,
        ], ['Accept' => 'application/json'])->assertCreated();
        $this->assertDatabaseHas('brands', ['name' => 'Shared Media Motors', 'logo' => $mediaPath]);
        $this->actingAs($admin)->deleteJson('/api/admin/media/'.$mediaId)->assertStatus(409);
        Storage::disk('media')->assertExists($mediaPath);
    }

    public function test_homepage_references_protect_shared_media_from_deletion(): void
    {
        Storage::fake('media');
        $admin = $this->adminWithPermissions('media.delete');
        $path = 'images/2026/09/homepage.png';
        Storage::disk('media')->put($path, 'image bytes');
        $media = Media::create([
            'file_name' => 'homepage.png', 'original_name' => 'homepage.png', 'disk' => 'media', 'path' => $path,
            'mime' => 'image/png', 'extension' => 'png', 'size' => 12, 'width' => 1, 'height' => 1, 'created_by' => $admin->id,
        ]);
        HomepageSection::create([
            'section_key' => 'hero', 'enabled' => true, 'display_order' => 1,
            'config' => ['media_id' => $media->id],
        ]);

        $this->actingAs($admin)->deleteJson('/api/admin/media/'.$media->id)
            ->assertStatus(409)
            ->assertJsonFragment(['Homepage section']);

        Storage::disk('media')->assertExists($path);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Media test', 'slug' => 'media-test-'.uniqid(), 'is_system' => false]);
        $permissions = collect($slugs)->map(fn ($slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Media']));
        $role->permissions()->sync($permissions->modelKeys());
        $admin->roles()->attach($role);
        return $admin;
    }
}
