<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

class AuthTest extends AppTestCase
{
    public function test_registration_creates_a_pending_account(): void
    {
        $this->post('/register', ['display_name' => 'ທອງ ຊົ່ງ', 'email' => 'Thong@Example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123'])
            ->assertRedirect('/profile');

        $u = User::where('email', 'thong@example.com')->firstOrFail();
        $this->assertSame('pending', $u->role);
        $this->assertAuthenticatedAs($u);
    }

    public function test_registration_rejects_short_passwords(): void
    {
        $this->post('/register', ['display_name' => 'A', 'email' => 'a@b.com', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_role_cannot_be_mass_assigned_on_register(): void
    {
        $this->post('/register', ['display_name' => 'X', 'email' => 'x@y.com', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'role' => 'admin']);
        $this->assertSame('pending', User::where('email', 'x@y.com')->value('role'));
    }

    public function test_login_logout_and_lockout(): void
    {
        $u = $this->user('member', ['email' => 'm@test.local']);
        $this->post('/login', ['email' => 'm@test.local', 'password' => 'Secret123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'm@test.local', 'password' => 'wrong']);
        }
        $this->assertNotNull($u->fresh()->locked_until);
        $this->post('/login', ['email' => 'm@test.local', 'password' => 'Secret123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_disabled_or_rejected_users_cannot_sign_in(): void
    {
        $this->user('member', ['email' => 'd@test.local', 'is_disabled' => true]);
        $this->post('/login', ['email' => 'd@test.local', 'password' => 'Secret123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_open_redirect_is_blocked(): void
    {
        $this->user('member', ['email' => 'r@test.local']);
        $this->post('/login', ['email' => 'r@test.local', 'password' => 'Secret123!', 'redirect' => '//evil.example'])->assertRedirect('/dashboard');
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $u = $this->user('member', ['email' => 'p@test.local']);
        $this->post('/reset-password/email', ['email' => 'p@test.local'])->assertSessionHas('success');

        $token = null;
        Notification::assertSentTo($u, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->post('/reset-password', ['token' => $token, 'email' => 'p@test.local', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertRedirect('/login');
        $this->post('/login', ['email' => 'p@test.local', 'password' => 'NewPass123'])->assertRedirect('/dashboard');
        $this->assertTrue(class_exists(ResetPassword::class));
    }

    public function test_google_routes_are_disabled_without_configuration(): void
    {
        config(['services.google.client_id' => null]);
        $this->get('/auth/google')->assertNotFound();
        $this->get('/login')->assertDontSee('ດຳເນີນການດ້ວຍ Google');
    }
}
