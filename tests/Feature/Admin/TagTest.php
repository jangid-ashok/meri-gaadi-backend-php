<?php

namespace Tests\Feature\Admin;

use App\Models\Admin\Tag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_crud_and_active_lookup_work(): void
    {
        $admin = $this->adminWithPermissions('tags.view', 'tags.create', 'tags.update', 'tags.delete');

        $created = $this->actingAs($admin)->postJson('/api/admin/tags', [
            'name' => '  Electric Cars  ',
            'description' => 'Battery-powered vehicles.',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $created->assertCreated()->assertJsonPath('data.name', 'Electric Cars')->assertJsonPath('data.slug', 'electric-cars')->assertJsonPath('data.blogs_count', 0);
        $tag = Tag::firstOrFail();

        $this->actingAs($admin)->getJson('/api/admin/tags/'.$tag->id)->assertOk()->assertJsonPath('data.name', 'Electric Cars');
        $this->actingAs($admin)->putJson('/api/admin/tags/'.$tag->id, ['name' => 'EV Cars', 'status' => 'inactive'])->assertOk();
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'EV Cars', 'slug' => 'ev-cars', 'status' => 'inactive']);

        $this->actingAs($admin)->getJson('/api/admin/tags/active')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($admin)->deleteJson('/api/admin/tags/'.$tag->id)->assertNoContent();
        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    }

    public function test_tag_permissions_are_enforced(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/admin/tags')->assertForbidden();
        $this->actingAs($admin)->postJson('/api/admin/tags', $this->tagData())->assertForbidden();

        $viewer = $this->adminWithPermissions('tags.view');
        $this->actingAs($viewer)->getJson('/api/admin/tags')->assertOk();
        $this->actingAs($viewer)->postJson('/api/admin/tags', $this->tagData())->assertForbidden();
    }

    public function test_duplicate_names_and_slug_collisions_are_handled(): void
    {
        $admin = $this->adminWithPermissions('tags.create');
        Tag::create($this->tagData(['name' => 'SUV', 'slug' => 'suv']));

        $this->actingAs($admin)->postJson('/api/admin/tags', $this->tagData(['name' => 'suv']))->assertUnprocessable()->assertJsonValidationErrors(['name']);
        $response = $this->actingAs($admin)->postJson('/api/admin/tags', $this->tagData(['name' => 'SUV Cars', 'slug' => 'suv']))->assertCreated();
        $response->assertJsonPath('data.slug', 'suv-cars');
        $this->actingAs($admin)->postJson('/api/admin/tags', $this->tagData(['name' => 'SUV!!!']))->assertCreated()->assertJsonPath('data.slug', 'suv-2');
        $this->actingAs($admin)->postJson('/api/admin/tags', $this->tagData(['name' => '   ']))->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_search_status_filter_and_shared_pagination_work(): void
    {
        $admin = $this->adminWithPermissions('tags.view');
        foreach (range(1, 16) as $number) {
            Tag::create($this->tagData(['name' => 'Tag '.$number, 'slug' => 'tag-'.$number, 'status' => $number === 1 ? 'inactive' : 'active']));
        }

        $this->actingAs($admin)->getJson('/api/admin/tags?search=Tag%201&status=inactive')->assertOk()->assertJsonPath('data.total', 1);
        $this->actingAs($admin)->getJson('/api/admin/tags?page=2&per_page=10')->assertOk()->assertJsonPath('data.current_page', 2)->assertJsonPath('data.per_page', 10)->assertJsonPath('data.from', 11)->assertJsonPath('data.to', 16);
    }

    public function test_tag_with_blog_usage_cannot_be_deleted(): void
    {
        $admin = $this->adminWithPermissions('tags.delete');
        $tag = Tag::create($this->tagData());
        $blog = \App\Models\Admin\Blogs::create(['title' => 'Tagged blog', 'slug' => 'tagged-blog', 'status' => 'inactive', 'is_deleted' => 'no']);
        $tag->blogs()->attach($blog->id);

        $this->actingAs($admin)->deleteJson('/api/admin/tags/'.$tag->id)->assertStatus(409);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Tag tester '.uniqid(), 'slug' => 'tag-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Tags']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);

        return $admin;
    }

    private function tagData(array $overrides = []): array
    {
        return array_merge(['name' => 'Maruti', 'slug' => 'maruti', 'description' => 'Tag description.', 'status' => 'active', 'sort_order' => 1], $overrides);
    }
}