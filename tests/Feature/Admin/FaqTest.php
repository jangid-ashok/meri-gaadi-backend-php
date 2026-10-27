<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_crud_and_active_lookup_work(): void
    {
        $admin = $this->adminWithPermissions('faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete');
        $category = Category::create([
            'name' => 'General info',
            'slug' => 'general-info',
            'category_type' => 'content',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $created = $this->actingAs($admin)->postJson('/api/admin/faqs', [
            'category_id' => $category->id,
            'question' => '  What is the mileage of this car?  ',
            'answer' => 'Mileage depends on engine, usage, and maintenance history.',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.question', 'What is the mileage of this car?')
            ->assertJsonPath('data.slug', 'what-is-the-mileage-of-this-car')
            ->assertJsonPath('data.category.id', $category->id);

        $faq = Faq::firstOrFail();

        $this->actingAs($admin)->getJson('/api/admin/faqs/'.$faq->id)->assertOk()->assertJsonPath('data.question', 'What is the mileage of this car?');
        $this->actingAs($admin)->putJson('/api/admin/faqs/'.$faq->id, [
            'question' => 'What is mileage?',
            'answer' => 'Mileage measures distance covered per unit of fuel or energy.',
            'status' => 'inactive',
        ])->assertOk();
        $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'question' => 'What is mileage?', 'slug' => 'what-is-mileage', 'status' => 'inactive']);

        $this->actingAs($admin)->getJson('/api/admin/faqs/active')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($admin)->deleteJson('/api/admin/faqs/'.$faq->id)->assertNoContent();
        $this->assertSoftDeleted('faqs', ['id' => $faq->id]);
    }

    public function test_faq_permissions_are_enforced(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/admin/faqs')->assertForbidden();
        $this->actingAs($admin)->postJson('/api/admin/faqs', $this->faqData())->assertForbidden();

        $viewer = $this->adminWithPermissions('faqs.view');
        $this->actingAs($viewer)->getJson('/api/admin/faqs')->assertOk();
        $this->actingAs($viewer)->postJson('/api/admin/faqs', $this->faqData())->assertForbidden();
    }

    public function test_duplicate_questions_and_filters_are_handled(): void
    {
        $admin = $this->adminWithPermissions('faqs.view', 'faqs.create');
        $category = Category::create([
            'name' => 'Buying guide',
            'slug' => 'buying-guide',
            'category_type' => 'content',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        Faq::create([
            'category_id' => $category->id,
            'question' => 'What should I check before buying a used car?',
            'answer' => 'Check documents, service records, and inspection reports.',
            'status' => 'active',
            'sort_order' => 1,
            'slug' => 'what-should-i-check-before-buying-a-used-car',
        ]);

        $this->actingAs($admin)->postJson('/api/admin/faqs', $this->faqData([
            'category_id' => $category->id,
            'question' => 'what should i check before buying a used car?',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['question']);

        $this->actingAs($admin)->getJson('/api/admin/faqs?status=active&category_id='.$category->id)->assertOk()->assertJsonPath('data.total', 1);
    }

    public function test_search_and_shared_pagination_work(): void
    {
        $admin = $this->adminWithPermissions('faqs.view');
        $category = Category::create([
            'name' => 'General',
            'slug' => 'general',
            'category_type' => 'content',
            'status' => 'active',
            'sort_order' => 3,
        ]);

        foreach (range(1, 16) as $number) {
            Faq::create([
                'category_id' => $category->id,
                'question' => 'FAQ '.$number.' about service and mileage',
                'answer' => 'Answer '.$number.' includes maintenance and mileage guidance.',
                'status' => $number === 1 ? 'inactive' : 'active',
                'sort_order' => $number,
                'slug' => 'faq-'.$number.'-about-service-and-mileage',
            ]);
        }

        $this->actingAs($admin)->getJson('/api/admin/faqs?search=maintenance&status=active')->assertOk()->assertJsonPath('data.total', 15);
        $this->actingAs($admin)->getJson('/api/admin/faqs?page=2&per_page=10')->assertOk()->assertJsonPath('data.current_page', 2)->assertJsonPath('data.per_page', 10)->assertJsonPath('data.from', 11)->assertJsonPath('data.to', 16);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'FAQ tester '.uniqid(), 'slug' => 'faq-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'FAQs']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);

        return $admin;
    }

    private function faqData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => null,
            'question' => 'How often should I service my car?',
            'answer' => 'Most cars should be serviced every 6 to 12 months or as recommended in the manual.',
            'status' => 'active',
            'sort_order' => 1,
        ], $overrides);
    }
}
