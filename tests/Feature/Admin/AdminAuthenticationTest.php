<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_url_redirects_to_the_shared_login_page(): void
    {
        $this->get('/admin/login')->assertRedirect('/login');
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_cannot_login_to_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('message');

        $this->assertGuest();
    }

    public function test_admin_routes_require_an_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_logout(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post('/admin/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_admin_password_reset_request_is_generic_and_notifies_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->post('/admin/forgot-password', ['email' => $admin->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($admin, AdminResetPasswordNotification::class);
    }

    public function test_profile_and_password_pages_require_admin(): void
    {
        $this->get('/admin/profile')->assertRedirect('/login');
        $this->get('/admin/change-password')->assertRedirect('/login');
    }
}