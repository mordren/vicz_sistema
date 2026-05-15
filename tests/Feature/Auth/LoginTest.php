<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_to_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('VICZ Implementos');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('secret'),
            'active' => true,
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'admin@test.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('correct'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'admin@test.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
