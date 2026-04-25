<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jambo's NotificationEventSubscriber fans out a
        // UserSignupNotification to all admin users on Registered.
        // spatie/permission throws RoleDoesNotExist when the role
        // isn't seeded — RefreshDatabase rebuilds the DB but doesn't
        // run seeders, so we seed the two roles the production seeder
        // would have created.
        // Jambo extended spatie/permission's roles table with a NOT NULL
        // `title` column for the admin UI; firstOrCreate needs it set.
        Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['title' => 'Administrator'],
        );
        Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'web'],
            ['title' => 'User'],
        );
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // Jambo's RegisteredUserController requires first_name/last_name/
        // username on top of email + password (vanilla Breeze just took
        // a single 'name'). New signups always land on the public
        // frontend, never /app — the admin role is hand-assigned only.
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name'  => 'User',
            'username'   => 'testuser_' . uniqid(),
            'email'      => 'test_' . uniqid() . '@example.com',
            'password'   => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }
}
