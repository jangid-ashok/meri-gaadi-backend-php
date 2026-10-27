<?php

namespace Tests\Feature\Admin;

use App\Models\{Brand, CarModel, Category, Media, Permission, Role, User};
use App\Models\Admin\Blogs as Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_seo_is_managed_once_and_returned_with_social_metadata(): void
    {
        $admin=$this->adminWithPermissions('seo.view','seo.create','seo.update','seo.delete','media.view','media.delete');
        $brand=Brand::create(['name'=>'SEO Motors','slug'=>'seo-motors','description'=>'Automotive brand.','status'=>'active']);
        $car=CarModel::create(['brand_id'=>$brand->id,'name'=>'SEO Car','slug'=>'seo-car','short_description'=>'Factual car description.','status'=>'active']);
        $og=$this->media($admin,'og.jpg');
        $payload=$this->payload(['entity_type'=>'car','entity_id'=>$car->id,'meta_title'=>'SEO Car: price and variants','meta_description'=>'Factual description for the SEO Car.','canonical_url'=>'https://www.example.test/cars/seo-car','robots'=>'index, follow','og_image_id'=>$og->id]);

        $created=$this->actingAs($admin)->postJson('/api/admin/seo',$payload)->assertCreated()->assertJsonPath('data.entity_label','SEO Car');
        $seoId=$created->json('data.id');
        $this->actingAs($admin)->getJson('/api/admin/seo?per_page=10')->assertOk()->assertJsonPath('data.total',1);
        $this->assertDatabaseHas('seo_metas',['seoable_type'=>CarModel::class,'seoable_id'=>$car->id,'og_image_id'=>$og->id]);
        $this->actingAs($admin)->postJson('/api/admin/seo',$payload)->assertUnprocessable()->assertJsonValidationErrors(['entity_id']);
        $this->getJson('/api/seo/car/seo-car')->assertOk()
            ->assertJsonPath('data.seo.title','SEO Car: price and variants')
            ->assertJsonPath('data.seo.robots','index, follow')
            ->assertJsonPath('data.seo.og.image',Media::findOrFail($og->id)->url)
            ->assertJsonPath('data.seo.twitter.title','SEO Car: price and variants');
        $this->actingAs($admin)->deleteJson('/api/admin/media/'.$og->id)->assertStatus(409);

        $twitter=$this->media($admin,'twitter.jpg');
        $this->actingAs($admin)->putJson('/api/admin/seo/'.$seoId,$this->payload(['meta_title'=>'Updated SEO Car','robots'=>'noindex, follow','og_image_id'=>null,'twitter_image_id'=>$twitter->id]))
            ->assertOk()->assertJsonPath('data.twitter_image.id',$twitter->id);
        $this->getJson('/api/seo/car/seo-car')->assertOk()->assertJsonPath('data.seo.title','Updated SEO Car')->assertJsonPath('data.seo.robots','noindex, follow');
        $this->assertDatabaseHas('seo_metas',['id'=>$seoId,'twitter_image_id'=>$twitter->id]);
    }

    public function test_homepage_metadata_fallback_validation_and_permissions(): void
    {
        $brand=Brand::create(['name'=>'Fallback Brand','slug'=>'fallback-brand','description'=>'A factual brand description.','status'=>'active']);
        $this->getJson('/api/seo/brand/fallback-brand')->assertOk()
            ->assertJsonPath('data.seo.title','Fallback Brand Cars & Models | Meri Gaadi')
            ->assertJsonPath('data.seo.description','A factual brand description.');
        $category=Category::create(['name'=>'SEO Articles','slug'=>'seo-articles','category_type'=>'automotive','status'=>'active']);
        Article::create(['user_id'=>User::factory()->create(['is_admin'=>true])->id,'title'=>'Fallback Article','slug'=>'fallback-article','description'=>'Article body.','excerpt'=>'Legacy article excerpt.','category_id'=>$category->id,'status'=>'active','is_deleted'=>'no','publication_status'=>'published','published_at'=>now(),'meta_title'=>'Legacy Article Metadata','meta_description'=>'Legacy article description.']);
        $this->getJson('/api/articles/fallback-article')->assertOk()->assertJsonPath('data.seo.title','Legacy Article Metadata')->assertJsonPath('data.seo.description','Legacy article description.');
        $this->getJson('/api/seo/homepage')->assertOk()->assertJsonPath('data.seo.title','Meri Gaadi | Explore Cars');

        $admin=$this->adminWithPermissions('seo.create','seo.view','seo.update');
        $homepage=$this->payload(['entity_type'=>'homepage','meta_title'=>'Meri Gaadi Homepage','canonical_url'=>'https://www.example.test/']);
        $this->actingAs($admin)->postJson('/api/admin/seo',$homepage)->assertCreated()->assertJsonPath('data.entity_type','homepage');
        $this->getJson('/api/homepage')->assertOk()->assertJsonPath('data.seo.title','Meri Gaadi Homepage');
        $this->actingAs($admin)->postJson('/api/admin/seo',$this->payload(['entity_type'=>'homepage','meta_title'=>'Duplicate']))->assertUnprocessable();

        $invalid=$this->payload(['entity_type'=>'brand','entity_id'=>$brand->id,'meta_title'=>'','canonical_url'=>'javascript:alert(1)','robots'=>'all','twitter_card_type'=>'summary_card']);
        $this->actingAs($admin)->postJson('/api/admin/seo',$invalid)->assertUnprocessable()->assertJsonValidationErrors(['meta_title','canonical_url','robots','twitter_card_type']);
        $noPermission=$this->adminWithPermissions();
        $this->actingAs($noPermission)->getJson('/api/admin/seo')->assertForbidden();
    }

    private function payload(array $overrides=[]): array
    {
        return array_merge(['meta_title'=>'Metadata title','meta_description'=>null,'canonical_url'=>null,'og_title'=>null,'og_description'=>null,'og_image_id'=>null,'robots'=>'index, follow','twitter_card_type'=>'summary_large_image','twitter_title'=>null,'twitter_description'=>null,'twitter_image_id'=>null],$overrides);
    }

    private function media(User $admin,string $name): Media
    {
        return Media::create(['disk'=>'media','file_name'=>$name,'path'=>'images/'.$name,'mime'=>'image/jpeg','extension'=>'jpg','size'=>100,'original_name'=>$name,'created_by'=>$admin->id]);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $user=User::factory()->create(['is_admin'=>true]);
        $role=Role::create(['name'=>'SEO test','slug'=>'seo-test-'.uniqid(),'is_system'=>false]);
        $permissions=collect($slugs)->map(fn($slug)=>Permission::firstOrCreate(['slug'=>$slug],['name'=>$slug,'module'=>'SEO']));
        $role->permissions()->sync($permissions->modelKeys());$user->roles()->attach($role);
        return $user;
    }
}
