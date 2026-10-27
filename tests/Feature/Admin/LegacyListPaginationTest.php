<?php

namespace Tests\Feature\Admin;

use App\Models\Admin\BlogCategory;
use App\Models\Admin\Blogs;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_blog_lists_use_shared_pagination_and_search_state(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $permission = Permission::create(['name' => 'View articles', 'slug' => 'articles.view', 'module' => 'Articles']);
        $role = Role::create(['name' => 'Article reader', 'slug' => 'article-reader']);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);

        foreach (range(1, 16) as $number) {
            BlogCategory::create(['title' => 'Category '.$number, 'slug' => 'category-'.$number, 'sub_title' => 'Content']);
            Blogs::create(['title' => 'Blog '.$number, 'slug' => 'blog-'.$number, 'sub_title' => 'Content', 'status' => 'active']);
        }

        $this->actingAs($admin)->get('/admin/blogs?search=Blog&page=99&per_page=10')
            ->assertOk()->assertSee('Showing 11')->assertSee('of 16')->assertSee('page=2');
        $this->actingAs($admin)->get('/admin/blog-categories?search=Category&page=99&per_page=10')
            ->assertOk()->assertSee('Showing 11')->assertSee('of 16')->assertSee('page=2');
        $this->actingAs($admin)->get('/admin/blogs?search=missing')
            ->assertOk()->assertSee('No blogs match your search.');
        $this->actingAs($admin)->get('/admin/blog-categories?search=missing')
            ->assertOk()->assertSee('No categories match your search.');
    }
}
