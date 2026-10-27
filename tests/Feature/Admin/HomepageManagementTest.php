<?php

namespace Tests\Feature\Admin;

use App\Models\{Brand, CarModel, HomepageSection, Media, Permission, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_requires_permission_and_changes_public_homepage(): void
    {
        $viewer = $this->adminWithPermissions('homepage.view');
        $this->actingAs($viewer)->getJson('/api/admin/homepage')->assertOk();
        $this->actingAs($viewer)->putJson('/api/admin/homepage', $this->payload())->assertForbidden();

        $editor = $this->adminWithPermissions('homepage.view', 'homepage.update');
        $this->actingAs($editor)->putJson('/api/admin/homepage', $this->payload())->assertOk();
        $this->getJson('/api/homepage')->assertOk()
            ->assertJsonPath('data.hero.title', 'Homepage API Test')
            ->assertJsonPath('data.hero.cta.url', '/cars')
            ->assertJsonMissingPath('data.promotion');
        $this->assertDatabaseHas('homepage_sections', ['section_key' => 'hero', 'enabled' => true]);
    }

    public function test_selected_content_is_referenced_and_disabled_items_are_not_published(): void
    {
        $brand = Brand::create(['name' => 'Homepage Motors', 'slug' => 'homepage-motors', 'status' => 'active']);
        $car = CarModel::create(['brand_id' => $brand->id, 'name' => 'Homepage Car', 'slug' => 'homepage-car', 'status' => 'active']);
        $admin = $this->adminWithPermissions('homepage.update');
        $payload = $this->payload();
        $payload['sections']['featured_cars']['config']['item_ids'] = [$car->id];
        $payload['sections']['featured_cars']['enabled'] = true;
        $payload['sections']['popular_brands']['enabled'] = false;
        $this->actingAs($admin)->putJson('/api/admin/homepage', $payload)->assertOk();

        $this->getJson('/api/homepage')->assertOk()
            ->assertJsonPath('data.featured_cars.items.0.id', $car->id)
            ->assertJsonMissingPath('data.popular_brands');
        $this->assertSame([$car->id], HomepageSection::where('section_key', 'featured_cars')->firstOrFail()->config['item_ids']);
    }

    public function test_homepage_image_fields_reject_non_image_media(): void
    {
        $video = Media::create(['file_name' => 'clip.mp4', 'disk' => 'media', 'path' => 'videos/clip.mp4', 'mime' => 'video/mp4', 'size' => 100]);
        $admin = $this->adminWithPermissions('homepage.update', 'media.view');
        $payload = $this->payload();
        $payload['sections']['hero']['config']['media_id'] = $video->id;

        $this->actingAs($admin)->putJson('/api/admin/homepage', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sections.hero.config.media_id']);
    }

    private function payload(): array
    {
        $sections = [
            'hero' => ['enabled' => true, 'title' => 'Homepage API Test', 'display_order' => 1, 'config' => ['heading' => 'Homepage API Test', 'subheading' => 'Test subtitle', 'cta_text' => 'Explore', 'cta_url' => '/cars']],
            'featured_cars' => ['enabled' => false, 'title' => 'Featured Cars', 'display_order' => 2, 'item_limit' => 6, 'config' => ['item_ids' => []]],
            'popular_brands' => ['enabled' => true, 'title' => 'Popular Brands', 'display_order' => 3, 'item_limit' => 8, 'config' => ['item_ids' => []]],
            'latest_articles' => ['enabled' => false, 'title' => 'Latest Articles', 'display_order' => 4, 'item_limit' => 6, 'config' => []],
            'car_categories' => ['enabled' => false, 'title' => 'Categories', 'display_order' => 5, 'item_limit' => 8, 'config' => ['item_ids' => []]],
            'promotion' => ['enabled' => false, 'title' => 'Promotion', 'display_order' => 6, 'config' => ['description' => '', 'cta_text' => '', 'cta_url' => '']],
        ];
        return ['sections' => $sections];
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Homepage test', 'slug' => 'homepage-test-'.uniqid(), 'is_system' => false]);
        $permissions = collect($slugs)->map(fn ($slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Homepage']));
        $role->permissions()->sync($permissions->modelKeys());
        $user->roles()->attach($role);
        return $user;
    }
}
