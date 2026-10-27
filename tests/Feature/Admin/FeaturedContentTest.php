<?php

namespace Tests\Feature\Admin;

use App\Models\{FeaturedContent, HomepageSection, Media, Permission, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_public_filtering_and_media_reference_protection(): void
    {
        $admin=$this->adminWithPermissions('featured_content.view','featured_content.create','featured_content.update','featured_content.delete','media.view','media.delete');
        $media=$this->media($admin);
        $payload=['title'=>'Explore Electric Cars','description'=>'Discover the latest EVs.','media_id'=>$media->id,'cta_text'=>'Explore EV Cars','cta_url'=>'/cars?fuel=electric','placement'=>'homepage_promotion','status'=>'active','display_order'=>2];
        $created=$this->actingAs($admin)->postJson('/api/admin/featured-content',$payload)->assertCreated()->assertJsonPath('data.image.id',$media->id);
        $id=$created->json('data.id');
        $this->getJson('/api/featured-content?placement=homepage_promotion')->assertOk()->assertJsonPath('data.0.title','Explore Electric Cars')->assertJsonPath('data.0.cta.url','/cars?fuel=electric');
        HomepageSection::updateOrCreate(['section_key'=>'promotion'],['title'=>'Promotion','enabled'=>true,'display_order'=>6,'config'=>['description'=>'','media_id'=>null,'cta_text'=>'','cta_url'=>'']]);
        $this->getJson('/api/homepage')->assertOk()->assertJsonPath('data.promotion.items.0.id',$id);
        $this->actingAs($admin)->deleteJson('/api/admin/media/'.$media->id)->assertStatus(409);
        $replacement=$this->media($admin,'banner-replacement.jpg');
        $payload['title']='Updated EV Banner';$payload['status']='inactive';$payload['display_order']=1;$payload['media_id']=$replacement->id;
        $this->actingAs($admin)->putJson('/api/admin/featured-content/'.$id,$payload)->assertOk()->assertJsonPath('data.title','Updated EV Banner')->assertJsonPath('data.image.id',$replacement->id);
        $this->getJson('/api/featured-content')->assertOk()->assertJsonMissing(['title'=>'Updated EV Banner']);
        $this->actingAs($admin)->deleteJson('/api/admin/featured-content/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('featured_contents',['id'=>$id]);
    }

    public function test_validation_and_permission_are_enforced(): void
    {
        $unauthorized=$this->adminWithPermissions();
        $this->actingAs($unauthorized)->postJson('/api/admin/featured-content',[])->assertForbidden();
        $admin=$this->adminWithPermissions('featured_content.create','media.view');
        $this->actingAs($admin)->postJson('/api/admin/featured-content',['title'=>'','media_id'=>500,'cta_url'=>'javascript:alert(1)','placement'=>'random','status'=>'active','display_order'=>-1])->assertUnprocessable()->assertJsonValidationErrors(['title','media_id','cta_url','placement','display_order']);
    }

    public function test_public_featured_content_is_bounded_to_one_hundred_items(): void
    {
        $media = $this->media(User::factory()->create(['is_admin' => true]));
        foreach (range(1, 101) as $number) {
            FeaturedContent::create(['title' => 'Banner '.$number, 'media_id' => $media->id, 'placement' => 'homepage_hero', 'status' => 'active', 'display_order' => $number]);
        }

        $this->getJson('/api/featured-content')->assertOk()->assertJsonCount(100, 'data');
    }

    private function media(User $admin, string $fileName='banner.jpg'): Media
    {
        return Media::create(['disk'=>'media','file_name'=>$fileName,'path'=>'images/'.$fileName,'mime'=>'image/jpeg','extension'=>'jpg','size'=>100,'original_name'=>$fileName,'created_by'=>$admin->id]);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $user=User::factory()->create(['is_admin'=>true]);
        $role=Role::create(['name'=>'Featured content test','slug'=>'featured-content-test-'.uniqid(),'is_system'=>false]);
        $permissions=collect($slugs)->map(fn($slug)=>Permission::firstOrCreate(['slug'=>$slug],['name'=>$slug,'module'=>'Featured Content']));
        $role->permissions()->sync($permissions->modelKeys());$user->roles()->attach($role);
        return $user;
    }
}
