<?php

namespace Modules\Content\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Vj;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin CRUD for VJs.
 *
 * VjController::update() and the admin.vjs.update route existed from the start,
 * but the listing only ever rendered a Delete button — so a VJ could be created
 * and destroyed, never corrected. These tests pin the edit path, and the profile
 * fields that feed the public page's Person schema.
 */
class VjAdminTest extends TestCase
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

    public function test_admin_can_update_a_vj_profile(): void
    {
        $vj = Vj::create(['name' => 'Vj Junior', 'slug' => 'vj-junior']);

        $this->actingAs($this->admin())
            ->put(route('admin.vjs.update', $vj), [
                'name'        => 'Vj Junior',
                'slug'        => 'vj-junior',
                'colour'      => '#1A98FF',
                'photo_url'   => '/storage/media/vjs/junior.jpg',
                'description' => "Uganda's best-known film translator.",
                'youtube_url' => 'https://youtube.com/@vjjunior',
                'tiktok_url'  => 'https://tiktok.com/@vjjunior',
            ])
            ->assertRedirect();

        $vj->refresh();

        $this->assertSame('/storage/media/vjs/junior.jpg', $vj->photo_url);
        $this->assertSame('https://youtube.com/@vjjunior', $vj->youtube_url);
        $this->assertSame("Uganda's best-known film translator.", $vj->description);
    }

    /**
     * Blank inputs must land as null, not ''. StructuredData's array_filter
     * drops nulls, but an empty string survives it and would be emitted as a
     * real (broken) `sameAs` entry — and one malformed sameAs makes Google
     * discard the entire Person node, not just that entry.
     */
    public function test_blank_social_fields_are_stored_as_null_not_empty_string(): void
    {
        $vj = Vj::create(['name' => 'Vj Emmy', 'slug' => 'vj-emmy']);

        $this->actingAs($this->admin())
            ->put(route('admin.vjs.update', $vj), [
                'name'         => 'Vj Emmy',
                'slug'         => 'vj-emmy',
                'youtube_url'  => 'https://youtube.com/@vjemmy',
                'facebook_url' => '',
                'website_url'  => '',
            ])
            ->assertRedirect();

        $vj->refresh();

        $this->assertNull($vj->facebook_url);
        $this->assertNull($vj->website_url);
    }

    /**
     * A relative or junk value would be emitted verbatim into sameAs.
     */
    public function test_social_urls_must_be_absolute(): void
    {
        $vj = Vj::create(['name' => 'Vj Ice P', 'slug' => 'vj-ice-p']);

        $this->actingAs($this->admin())
            ->put(route('admin.vjs.update', $vj), [
                'name'        => 'Vj Ice P',
                'youtube_url' => '/not-a-real-url',
            ])
            ->assertSessionHasErrors('youtube_url');

        $this->assertNull($vj->refresh()->youtube_url);
    }

    /**
     * The edit modal must keep header/body/footer as direct children of
     * .modal-content.
     *
     * Bootstrap's modal-dialog-scrollable makes .modal-content a flex column
     * with `overflow: hidden` and lets .modal-body scroll inside it. Wrapping
     * all three in a <form> — the obvious way to build a modal form — makes the
     * form the single flex item, so it grows to its natural height and the
     * footer is silently clipped: on a form this tall there is then no reachable
     * Save button and the body will not scroll. The form therefore lives inside
     * .modal-body and the footer's submit button reaches it via the HTML5 `form`
     * attribute.
     */
    public function test_edit_modal_keeps_the_scrollable_layout_and_a_reachable_save_button(): void
    {
        $vj = Vj::create(['name' => 'Vj Junior', 'slug' => 'vj-junior']);

        $html = $this->actingAs($this->admin())
            ->get(route('dashboard.vjs'))
            ->assertOk()
            ->getContent();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        $content = $xp->query('//div[starts-with(@id,"vj-edit-")]//div[contains(@class,"modal-content")]')->item(0);
        $this->assertNotNull($content, 'No edit modal rendered.');

        $childClasses = [];
        foreach ($content->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $childClasses[] = $node->getAttribute('class');
            }
        }

        $this->assertCount(3, $childClasses, 'modal-content must have exactly header/body/footer as children.');
        $this->assertStringContainsString('modal-header', $childClasses[0]);
        $this->assertStringContainsString('modal-body', $childClasses[1]);
        $this->assertStringContainsString('modal-footer', $childClasses[2]);

        // The Save button sits in the footer, outside the form — it must still
        // resolve to a real form, or the modal cannot be submitted at all.
        $button = $xp->query('//div[contains(@class,"modal-footer")]/button[@type="submit" and @form]')->item(0);
        $this->assertNotNull($button, 'Footer has no submit button bound to a form.');

        $formId = $button->getAttribute('form');
        $this->assertNotNull(
            $xp->query('//form[@id="' . $formId . '"]')->item(0),
            "Save button points at form #{$formId}, which does not exist."
        );
    }

    /**
     * Once a photo and socials exist, they must actually reach the Person node
     * on the public page — that graph is the entity anchor for "vj junior".
     */
    public function test_saved_photo_and_socials_reach_the_public_person_schema(): void
    {
        $vj = Vj::create([
            'name'        => 'Vj Junior',
            'slug'        => 'vj-junior',
            'photo_url'   => 'https://cdn.example.com/junior.jpg',
            'youtube_url' => 'https://youtube.com/@vjjunior',
            'tiktok_url'  => 'https://tiktok.com/@vjjunior',
        ]);

        $html = $this->get(route('frontend.vj_detail', $vj->slug))->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        $person = null;
        foreach ($m[1] as $json) {
            $decoded = json_decode($json, true);
            if (($decoded['@type'] ?? null) === 'Person') {
                $person = $decoded;
            }
        }

        $this->assertNotNull($person, 'VJ hub emitted no Person graph.');
        $this->assertSame('https://cdn.example.com/junior.jpg', $person['image'][0]);
        $this->assertContains('https://youtube.com/@vjjunior', $person['sameAs']);
        $this->assertContains('https://tiktok.com/@vjjunior', $person['sameAs']);
    }
}
