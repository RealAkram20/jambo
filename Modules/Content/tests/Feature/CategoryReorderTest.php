<?php

namespace Modules\Content\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Category;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Drag-and-drop category ordering. The admin table PATCHes the full id
 * list in display order; position becomes sort_order, and the homepage
 * rails render in exactly that order (the shuffle was removed — admin
 * order IS the page order).
 */
class CategoryReorderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles live in a seeder, not a migration — RefreshDatabase leaves the
        // table empty. Matches tests/Feature/Admin/SuperAdminGuardTest.
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
    }

    private function admin(): User
    {
        $user = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'email'    => 'admin_' . uniqid() . '@test.local',
        ]);

        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_reorder_categories(): void
    {
        $a = Category::create(['name' => 'Action', 'slug' => 'action', 'sort_order' => 0]);
        $b = Category::create(['name' => 'Comedy', 'slug' => 'comedy', 'sort_order' => 1]);
        $c = Category::create(['name' => 'Drama', 'slug' => 'drama', 'sort_order' => 2]);

        $this->actingAs($this->admin())
            ->patchJson(route('admin.categories.reorder'), [
                'order' => [$c->id, $a->id, $b->id],
            ])
            ->assertOk();

        $this->assertSame(0, $c->refresh()->sort_order);
        $this->assertSame(1, $a->refresh()->sort_order);
        $this->assertSame(2, $b->refresh()->sort_order);
    }

    public function test_unknown_ids_are_rejected(): void
    {
        $a = Category::create(['name' => 'Action', 'slug' => 'action', 'sort_order' => 5]);

        $this->actingAs($this->admin())
            ->patchJson(route('admin.categories.reorder'), [
                'order' => [$a->id, 999999],
            ])
            ->assertUnprocessable();

        $this->assertSame(5, $a->refresh()->sort_order);
    }

    public function test_guests_and_non_admins_cannot_reorder(): void
    {
        $a = Category::create(['name' => 'Action', 'slug' => 'action', 'sort_order' => 0]);

        $this->patchJson(route('admin.categories.reorder'), ['order' => [$a->id]])
            ->assertUnauthorized();

        $this->actingAs(User::factory()->create([
            'username' => 'user_' . uniqid(),
            'email'    => 'user_' . uniqid() . '@test.local',
        ]))
            ->patchJson(route('admin.categories.reorder'), ['order' => [$a->id]])
            ->assertForbidden();
    }
}
