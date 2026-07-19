<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\ContentActivity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Performance dashboard "who did what" feed: super-admins can narrow it
 * to a single admin (?actor=<id>) to see what that person did in the
 * selected period; regular admins are pinned to their own actions and
 * the param must not let them read someone else's feed.
 */
class PerformanceActorFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $vjA;
    private User $vjB;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['user', 'admin', 'super-admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['title' => ucfirst($role)]);
        }

        $this->superAdmin = $this->makeAdmin('boss', 'super-admin');
        $this->vjA = $this->makeAdmin('vj_junior');
        $this->vjB = $this->makeAdmin('vj_emmy');

        $this->log($this->vjA, 'Inception - Vj Junior');
        $this->log($this->vjB, 'Private Resort - Vj Emmy');
    }

    private function makeAdmin(string $name, string $role = 'admin'): User
    {
        $user = User::factory()->create([
            'username' => $name . '_' . uniqid(),
            'email' => $name . '_' . uniqid() . '@test.local',
        ]);
        // Super-admins also carry the admin role (matches production
        // seeding — the routes are gated on role:admin).
        $user->assignRole($role === 'super-admin' ? ['admin', 'super-admin'] : $role);

        return $user;
    }

    private function log(User $actor, string $title): void
    {
        ContentActivity::create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->username,
            'action' => ContentActivity::ACTION_CREATED,
            'content_type' => 'movie',
            'content_id' => 1,
            'content_title' => $title,
            'created_at' => now(),
        ]);
    }

    public function test_super_admin_can_filter_the_feed_to_one_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('dashboard.performance', ['actor' => $this->vjA->id]))
            ->assertOk()
            ->assertSee('Inception - Vj Junior')
            ->assertDontSee('Private Resort - Vj Emmy');
    }

    public function test_super_admin_sees_everyone_without_a_filter(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('dashboard.performance'))
            ->assertOk()
            ->assertSee('Inception - Vj Junior')
            ->assertSee('Private Resort - Vj Emmy');
    }

    public function test_regular_admin_cannot_use_the_actor_param_to_read_another_feed(): void
    {
        $this->actingAs($this->vjA)
            ->get(route('dashboard.performance', ['actor' => $this->vjB->id]))
            ->assertOk()
            ->assertSee('Inception - Vj Junior')
            ->assertDontSee('Private Resort - Vj Emmy');
    }

    public function test_actor_filter_respects_the_period_window(): void
    {
        ContentActivity::create([
            'actor_id' => $this->vjA->id,
            'actor_name' => $this->vjA->username,
            'action' => ContentActivity::ACTION_CREATED,
            'content_type' => 'movie',
            'content_id' => 2,
            'content_title' => 'Old Upload - Vj Junior',
            'created_at' => now()->subDays(3),
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard.performance', ['actor' => $this->vjA->id, 'period' => 'day']))
            ->assertOk()
            ->assertSee('Inception - Vj Junior')
            ->assertDontSee('Old Upload - Vj Junior');
    }
}
