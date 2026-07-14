<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\TitleSplit;
use Modules\Monetization\app\Services\AuditLogger;

/**
 * Partner self-service content management, capability-gated:
 *
 *  - Ownership: the title must carry THIS partner's monetization
 *    split — the same definition the money uses. Display credits
 *    (movie_vj pivots) alone grant nothing.
 *  - can_edit_content / can_delete_content are super-admin-granted
 *    flags on the partner row, default off.
 *  - Editing is metadata-only: title, synopsis, year, rating, artwork,
 *    trailer. Video sources, pricing tier, publish status and
 *    runtime_minutes stay admin-only — runtime feeds the payout math
 *    and a partner must not be able to inflate their own minutes.
 *
 * Every action is audit-logged.
 */
class PartnerContentController extends PartnerBaseController
{
    public function edit(string $type, int $id)
    {
        $partner = $this->partner();
        $title = $this->resolveOwnedTitle($partner, $type, $id);

        abort_unless($partner->can_edit_content && $partner->isEnrolled(), 403,
            'You have not been granted content editing rights — contact the Jambo team.');

        return view('monetization::partner.content-edit', [
            'partner' => $partner,
            'type' => $type,
            'title' => $title,
        ]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $partner = $this->partner();
        $title = $this->resolveOwnedTitle($partner, $type, $id);

        abort_unless($partner->can_edit_content && $partner->isEnrolled(), 403);

        $data = $request->validate([
            'title' => 'required|string|max:190',
            'synopsis' => 'nullable|string|max:5000',
            'year' => 'nullable|integer|min:1900|max:'.(now()->year + 2),
            'rating' => 'nullable|string|max:20',
            'poster_url' => 'nullable|string|max:2048',
            'backdrop_url' => 'nullable|string|max:2048',
            'trailer_url' => 'nullable|string|max:2048',
        ]);

        $before = $title->only(array_keys($data));

        $title->fill($data);
        if ($title->isDirty('title')) {
            $title->slug = $this->uniqueSlug($title, $data['title']);
        }
        $title->save();

        AuditLogger::logDiff('partner_content.updated', $title, $before, $title->only(array_keys($data)));

        return redirect()
            ->route('partner.titles')
            ->with('success', "“{$title->title}” updated.");
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $partner = $this->partner();
        $title = $this->resolveOwnedTitle($partner, $type, $id);

        abort_unless($partner->can_delete_content && $partner->isEnrolled(), 403,
            'You have not been granted content deletion rights — contact the Jambo team.');

        $name = $title->title;

        AuditLogger::log('partner_content.deleted', $title, ['before' => [
            'title' => $name,
            'type' => $type,
            'id' => $title->getKey(),
        ]]);

        // Same delete path the admin CRUD uses (model events fire for
        // pivot/file cleanup). Historical earnings are unaffected:
        // qualified_views carry no FK and statements snapshot titles.
        $title->delete();

        return redirect()
            ->route('partner.titles')
            ->with('success', "Deleted “{$name}”. Past earnings on it remain in your statements.");
    }

    /**
     * Resolve movie/show AND require this partner's split on it —
     * ownership and existence collapse into one 404 so the endpoint
     * doesn't leak which titles exist.
     */
    protected function resolveOwnedTitle(MonetizationPartner $partner, string $type, int $id): Model
    {
        $model = $type === 'movie' ? Movie::find($id) : Show::find($id);

        $owned = $model && TitleSplit::query()
            ->where('partner_id', $partner->id)
            ->where('splittable_type', $model->getMorphClass())
            ->where('splittable_id', $model->getKey())
            ->exists();

        abort_unless($owned, 404);

        return $model;
    }

    /** Mirror the admin controllers' unique-slug convention. */
    protected function uniqueSlug(Model $model, string $title): string
    {
        $base = Str::slug($title) ?: 'title';
        $slug = $base;
        $i = 2;

        while ($model->newQuery()
            ->where('slug', $slug)
            ->whereKeyNot($model->getKey())
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
