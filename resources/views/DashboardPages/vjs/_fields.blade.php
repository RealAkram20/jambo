{{--
    The VJ field set, shared by the "Add New Vj" form and every row's Edit modal
    so the two can never drift apart.

    Props:
        vj  — Vj model when editing, null when creating
        uid — unique suffix for DOM ids (the media picker resolves its preview
              by a document-wide querySelector, so every instance on the page
              needs its own handle). See content::admin.partials.media-picker-field.
--}}
@php
    $vj  = $vj ?? null;
    $uid = $uid ?? 'new';

    // Which form submitted last? Every instance on this page reads the same
    // old() bag, so without this an Edit that fails validation would repopulate
    // the *Add* form with the edited VJ's name — and the admin, seeing a filled
    // form, could easily create a duplicate instead of retrying the edit.
    // The hidden _form marker below lets each instance recognise its own input.
    $formId  = (string) $uid;
    $isMine  = old('_form') === $formId;

    // old() only for the form that actually submitted; everyone else shows the
    // stored value (or empty, when creating).
    $val = function (string $key, $stored = null) use ($isMine) {
        return $isMine ? old($key, $stored) : $stored;
    };
@endphp

<input type="hidden" name="_form" value="{{ $formId }}">

<div class="form-group">
    <label class="form-label" for="vj-name-{{ $uid }}">Name<span> *</span></label>
    <input type="text" class="form-control" id="vj-name-{{ $uid }}" name="name"
        value="{{ $val('name', $vj->name ?? '') }}" placeholder="e.g. Vj Junior" required>
</div>

<div class="form-group">
    <label class="form-label" for="vj-slug-{{ $uid }}">Slug</label>
    <input type="text" class="form-control" id="vj-slug-{{ $uid }}" name="slug"
        value="{{ $val('slug', $vj->slug ?? '') }}" placeholder="auto-generated from name if empty">
</div>

<div class="form-group">
    <label class="form-label" for="vj-colour-{{ $uid }}">Colour</label>
    <input type="color" class="form-control form-control-color" id="vj-colour-{{ $uid }}" name="colour"
        value="{{ $val('colour', $vj->colour ?? '#1A98FF') }}">
</div>

{{-- The VJ's own photo. Until this existed the admin list drew a coloured
     square with a microphone glyph, and the public VJ page illustrated itself
     with a still from whichever film the VJ last narrated. It also feeds
     `image` on the Person node in that page's structured data. --}}
@include('content::admin.partials.media-picker-field', [
    'key'         => 'photo_url',
    'uid'         => 'photo_url_' . $uid,
    'label'       => 'Photo',
    'value'       => $val('photo_url', $vj->photo_url ?? ''),
    'accept'      => ['jpg', 'jpeg', 'png', 'webp'],
    'aspect'      => '1/1',
    'placeholder' => 'https://... or /storage/media/vjs/...',
])

<div class="form-group">
    <label class="form-label" for="vj-description-{{ $uid }}">Description</label>
    <textarea class="form-control large-text" id="vj-description-{{ $uid }}" name="description"
        rows="3">{{ $val('description', $vj->description ?? '') }}</textarea>
</div>

{{-- Social profiles. Not decoration: these are emitted as schema.org `sameAs`
     on the VJ's Person node, which is what tells Google that the "VJ Junior" on
     this site is the same entity as the "VJ Junior" with an existing audience on
     YouTube and TikTok. It is the strongest entity-disambiguation signal we
     have, and "vj junior" is the site's highest-volume query.

     Must be absolute http(s) — VjController rejects anything else, because one
     malformed sameAs makes Google discard the whole node, not just the bad
     entry. --}}
@foreach (\Modules\Content\app\Models\Vj::SOCIAL_FIELDS as $field => $label)
    <div class="form-group">
        <label class="form-label" for="vj-{{ $field }}-{{ $uid }}">{{ $label }}</label>
        <input type="url" class="form-control @error($field) is-invalid @enderror"
            id="vj-{{ $field }}-{{ $uid }}" name="{{ $field }}"
            value="{{ $val($field, $vj->{$field} ?? '') }}"
            placeholder="https://">
        @error($field) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
@endforeach
