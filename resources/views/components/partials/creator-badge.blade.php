{{--
    Renders who created a piece of content, as a badge in the admin lists.

    Expects:
      $model — a content model using TracksContentActivity (Movie, Show,
               Season, Episode). Eager-load `creator` on the query and call
               Model::hydrateCreatorLabels($paginator) once per page, or
               this costs a query per row.

    Why a partial: the movies and series lists render the same badge, and
    episodes will want it next. One file, one look.

    Why an em dash and not a name: content added before authorship tracking
    (the 2026_07_13 migration), or written by a seeder or console command,
    has no actor recorded. Guessing one would credit the wrong admin — and
    the Performance dashboard counts uploads per admin, so a guess here
    becomes a payout there.
--}}
@php
    // One name on the badge, the full name on hover — two admins can share
    // a first name, and this column is the visible half of the per-admin
    // upload credit.
    $creatorLabel = $model->creatorLabel();
    $creatorFullLabel = $model->creatorFullLabel();
@endphp

@if ($creatorLabel)
    <span class="badge bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center gap-1"
          style="font-size:10px;max-width:120px;"
          title="Added by {{ $creatorFullLabel }}">
        <i class="ph ph-user-circle" style="font-size:11px;flex:0 0 auto;"></i>
        <span class="text-truncate">{{ $creatorLabel }}</span>
    </span>
@else
    <span class="text-secondary" style="font-size:12px;"
          title="No creator recorded — added before authorship tracking, or by an import or seeder">—</span>
@endif
