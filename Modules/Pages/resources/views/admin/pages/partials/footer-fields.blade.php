{{--
    Footer — structured admin fields for the public site footer.
    Drives Modules/Frontend/resources/views/components/partials/footer-default.blade.php

    Drag-drop is powered by SortableJS (loaded inline below). Each column's
    links and the social icons row are sortable; the column titles, contact
    block, newsletter, copyright, and app store URLs are flat fields.

    Submitted form arrays are re-indexed on save (the controller re-keys
    via array_replace), so the index numbers in the DOM are just for HTML
    name uniqueness — drag order is preserved by walking the DOM order
    on submit.
--}}
@php
    $contact = $page->metaValue('contact', []);
    $columns = $page->metaValue('columns', []);
    $newsletter = $page->metaValue('newsletter', ['enabled' => true]);
    $socials = $page->metaValue('socials', []);

    while (count($columns) < 3) {
        $columns[] = ['title' => '', 'links' => []];
    }
@endphp

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Contact info column</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Email label</label>
                <input type="text" class="form-control" name="meta[contact][email_label]"
                       value="{{ old('meta.contact.email_label', $contact['email_label'] ?? '') }}"
                       placeholder="Email Us">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email address</label>
                <input type="text" class="form-control" name="meta[contact][email_address]"
                       value="{{ old('meta.contact.email_address', $contact['email_address'] ?? '') }}"
                       placeholder="customer@jambo.co">
            </div>
            <div class="col-md-6">
                <label class="form-label">Helpline label</label>
                <input type="text" class="form-control" name="meta[contact][helpline_label]"
                       value="{{ old('meta.contact.helpline_label', $contact['helpline_label'] ?? '') }}"
                       placeholder="Helpline Number">
            </div>
            <div class="col-md-6">
                <label class="form-label">Helpline phone</label>
                <input type="text" class="form-control" name="meta[contact][helpline_phone]"
                       value="{{ old('meta.contact.helpline_phone', $contact['helpline_phone'] ?? '') }}"
                       placeholder="+(480) 555-0103">
            </div>
        </div>
    </div>
</div>

@foreach ($columns as $ci => $col)
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Link column {{ $ci + 1 }}</h6>
            <button type="button" class="btn btn-sm btn-primary" data-footer-add="{{ $ci }}">
                <i class="ph ph-plus me-1"></i> Add link
            </button>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label">Column title</label>
                <input type="text" class="form-control"
                       name="meta[columns][{{ $ci }}][title]"
                       value="{{ $col['title'] ?? '' }}"
                       placeholder="e.g. Quick Links">
            </div>

            <p class="text-secondary small mb-3">
                Drag <i class="ph ph-list-dashes"></i> to reorder, <i class="ph ph-trash-simple"></i> to remove.
            </p>

            <ul class="list-unstyled mb-0 footer-link-list" data-footer-links data-col-index="{{ $ci }}">
                @foreach (($col['links'] ?? []) as $li => $link)
                    <li class="border rounded p-3 mb-2 footer-link-row" data-footer-link style="background:rgba(255,255,255,0.02);">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="text-secondary footer-link-handle" style="cursor:grab;" title="Drag to reorder">
                                <i class="ph ph-list-dashes"></i>
                            </span>
                            <span class="badge bg-primary-subtle text-primary-emphasis flex-grow-1" data-link-badge>Link {{ $li + 1 }}</span>
                            <button type="button" class="btn btn-sm btn-danger-subtle" data-footer-remove title="Remove link">
                                <i class="ph ph-trash-simple"></i>
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small text-uppercase text-secondary mb-1">Label</label>
                                <input type="text" class="form-control"
                                       data-link-field="label"
                                       name="meta[columns][{{ $ci }}][links][{{ $li }}][label]"
                                       value="{{ $link['label'] ?? '' }}"
                                       placeholder="e.g. Top Trending">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small text-uppercase text-secondary mb-1">URL</label>
                                <input type="text" class="form-control"
                                       data-link-field="url"
                                       name="meta[columns][{{ $ci }}][links][{{ $li }}][url]"
                                       value="{{ $link['url'] ?? '' }}"
                                       placeholder="/page or https://...">
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <button type="button" class="btn btn-ghost w-100 mt-3" data-footer-add="{{ $ci }}">
                <i class="ph ph-plus me-1"></i> Add another link
            </button>
        </div>
    </div>
@endforeach

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Newsletter</h6></div>
    <div class="card-body">
        {{-- Hidden 0 + checkbox 1 pattern: ensures something always
             posts so the controller can persist a "disabled" state. --}}
        <input type="hidden" name="meta[newsletter][enabled]" value="0">
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="newsletter_enabled" name="meta[newsletter][enabled]" value="1"
                   @checked(old('meta.newsletter.enabled', $newsletter['enabled'] ?? true))>
            <label class="form-check-label" for="newsletter_enabled">Show newsletter signup in the footer</label>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Section title</label>
                <input type="text" class="form-control" name="meta[newsletter][title]"
                       value="{{ old('meta.newsletter.title', $newsletter['title'] ?? '') }}"
                       placeholder="Newsletter">
            </div>
            <div class="col-md-4">
                <label class="form-label">Input placeholder</label>
                <input type="text" class="form-control" name="meta[newsletter][placeholder]"
                       value="{{ old('meta.newsletter.placeholder', $newsletter['placeholder'] ?? '') }}"
                       placeholder="Email">
            </div>
            <div class="col-md-4">
                <label class="form-label">Button label</label>
                <input type="text" class="form-control" name="meta[newsletter][button_label]"
                       value="{{ old('meta.newsletter.button_label', $newsletter['button_label'] ?? '') }}"
                       placeholder="Subscribe">
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Social media</h6>
        <button type="button" class="btn btn-sm btn-primary" data-social-add>
            <i class="ph ph-plus me-1"></i> Add social
        </button>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Section label</label>
            <input type="text" class="form-control" name="meta[follow_label]"
                   value="{{ old('meta.follow_label', $page->metaValue('follow_label', 'Follow Us')) }}"
                   placeholder="Follow Us">
        </div>

        <p class="text-secondary small mb-3">
            Drag to reorder. Icon class accepts Phosphor (<code>ph ph-instagram-logo</code>, <code>ph ph-x-logo</code>, <code>ph-fill ph-linkedin-logo</code>) or the bundled icon font (<code>icon icon-facebook-share</code>, <code>icon icon-youtube-share</code>).
        </p>

        <ul class="list-unstyled mb-0" data-socials-list>
            @foreach ($socials as $si => $social)
                <li class="border rounded p-2 mb-2 d-flex align-items-center gap-2 social-row" data-social-row>
                    <span class="text-secondary social-handle" style="cursor:grab;" title="Drag to reorder">
                        <i class="ph ph-list-dashes"></i>
                    </span>
                    <span class="text-white" style="width:24px;text-align:center;" data-social-preview>
                        <i class="{{ $social['icon'] ?? '' }}"></i>
                    </span>
                    <input type="text" class="form-control form-control-sm"
                           data-social-field="icon"
                           name="meta[socials][{{ $si }}][icon]"
                           value="{{ $social['icon'] ?? '' }}"
                           placeholder="ph ph-instagram-logo">
                    <input type="text" class="form-control form-control-sm"
                           data-social-field="url"
                           name="meta[socials][{{ $si }}][url]"
                           value="{{ $social['url'] ?? '' }}"
                           placeholder="https://...">
                    <button type="button" class="btn btn-sm btn-danger-subtle" data-social-remove title="Remove">
                        <i class="ph ph-trash-simple"></i>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Copyright bar</h6></div>
    <div class="card-body">
        <label class="form-label">Copyright text</label>
        <textarea class="form-control" rows="2"
                  name="meta[copyright]"
                  placeholder="&copy; 2026 JAMBO. All rights reserved.">{{ old('meta.copyright', $page->metaValue('copyright')) }}</textarea>
        <small class="text-secondary">HTML allowed (use <code>&amp;copy;</code> for ©, wrap brand text in <code>&lt;span class="text-primary"&gt;</code>).</small>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Download app</h6></div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Section title</label>
            <input type="text" class="form-control" name="meta[download_app_title]"
                   value="{{ old('meta.download_app_title', $page->metaValue('download_app_title', 'Download App')) }}"
                   placeholder="Download App">
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Google Play URL</label>
                <input type="url" class="form-control @error('meta.play_store_url') is-invalid @enderror"
                       name="meta[play_store_url]"
                       value="{{ old('meta.play_store_url', $page->metaValue('play_store_url')) }}"
                       placeholder="https://play.google.com/store/apps/details?id=...">
                @error('meta.play_store_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">App Store URL</label>
                <input type="url" class="form-control @error('meta.app_store_url') is-invalid @enderror"
                       name="meta[app_store_url]"
                       value="{{ old('meta.app_store_url', $page->metaValue('app_store_url')) }}"
                       placeholder="https://apps.apple.com/app/...">
                @error('meta.app_store_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <small class="text-secondary d-block mt-2">Leave a URL blank to hide that badge. Badge images are bundled with the theme — <code>frontend/images/footer/play-store.webp</code> and <code>app-store.webp</code>.</small>
    </div>
</div>

<template id="footer-link-template">
    <li class="border rounded p-3 mb-2 footer-link-row" data-footer-link style="background:rgba(255,255,255,0.02);">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="text-secondary footer-link-handle" style="cursor:grab;" title="Drag to reorder">
                <i class="ph ph-list-dashes"></i>
            </span>
            <span class="badge bg-primary-subtle text-primary-emphasis flex-grow-1" data-link-badge>New link</span>
            <button type="button" class="btn btn-sm btn-danger-subtle" data-footer-remove title="Remove link">
                <i class="ph ph-trash-simple"></i>
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-5">
                <label class="form-label small text-uppercase text-secondary mb-1">Label</label>
                <input type="text" class="form-control" data-link-field="label" name="" value="" placeholder="e.g. Top Trending">
            </div>
            <div class="col-md-7">
                <label class="form-label small text-uppercase text-secondary mb-1">URL</label>
                <input type="text" class="form-control" data-link-field="url" name="" value="" placeholder="/page or https://...">
            </div>
        </div>
    </li>
</template>

<template id="social-row-template">
    <li class="border rounded p-2 mb-2 d-flex align-items-center gap-2 social-row" data-social-row>
        <span class="text-secondary social-handle" style="cursor:grab;" title="Drag to reorder">
            <i class="ph ph-list-dashes"></i>
        </span>
        <span class="text-white" style="width:24px;text-align:center;" data-social-preview>
            <i class=""></i>
        </span>
        <input type="text" class="form-control form-control-sm" data-social-field="icon" name="" value="" placeholder="ph ph-instagram-logo">
        <input type="text" class="form-control form-control-sm" data-social-field="url" name="" value="" placeholder="https://...">
        <button type="button" class="btn btn-sm btn-danger-subtle" data-social-remove title="Remove">
            <i class="ph ph-trash-simple"></i>
        </button>
    </li>
</template>

{{-- SortableJS: lightweight, MIT, ~25KB. Loaded only on the footer
     admin page; the rest of the admin doesn't pay for it. --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    // ---- helpers ----------------------------------------------------------

    // Re-walk a list and rewrite all `name="meta[...][i][field]"` so the
    // submitted array order matches the visible drag order. Also keeps
    // the "Link N" badges in sync with their visible position.
    function reindex(list, namePattern) {
        list.querySelectorAll('[data-footer-link], [data-social-row]').forEach(function (row, idx) {
            row.querySelectorAll('input[data-link-field], input[data-social-field]').forEach(function (input) {
                var field = input.dataset.linkField || input.dataset.socialField;
                input.name = namePattern.replace('__I__', idx).replace('__FIELD__', field);
            });
            var badge = row.querySelector('[data-link-badge]');
            if (badge) badge.textContent = 'Link ' + (idx + 1);
        });
    }

    // ---- footer link columns ---------------------------------------------

    document.querySelectorAll('[data-footer-links]').forEach(function (list) {
        var colIndex = list.dataset.colIndex;

        new Sortable(list, {
            handle: '.footer-link-handle',
            animation: 150,
            onEnd: function () {
                reindex(list, 'meta[columns][' + colIndex + '][links][__I__][__FIELD__]');
            },
        });
    });

    document.addEventListener('click', function (e) {
        var add = e.target.closest('[data-footer-add]');
        if (add) {
            var colIndex = add.dataset.footerAdd;
            var list = document.querySelector('[data-footer-links][data-col-index="' + colIndex + '"]');
            if (!list) return;
            var tpl = document.getElementById('footer-link-template');
            list.insertAdjacentHTML('beforeend', tpl.innerHTML);
            reindex(list, 'meta[columns][' + colIndex + '][links][__I__][__FIELD__]');
            return;
        }

        var rm = e.target.closest('[data-footer-remove]');
        if (rm) {
            var row = rm.closest('[data-footer-link]');
            var listEl = row.closest('[data-footer-links]');
            row.remove();
            reindex(listEl, 'meta[columns][' + listEl.dataset.colIndex + '][links][__I__][__FIELD__]');
        }
    });

    // ---- social media row -------------------------------------------------

    var socialsList = document.querySelector('[data-socials-list]');
    if (socialsList) {
        new Sortable(socialsList, {
            handle: '.social-handle',
            animation: 150,
            onEnd: function () {
                reindex(socialsList, 'meta[socials][__I__][__FIELD__]');
            },
        });

        // Live icon preview as admin types — saves a save+refresh cycle.
        socialsList.addEventListener('input', function (e) {
            if (e.target.dataset.socialField !== 'icon') return;
            var preview = e.target.closest('[data-social-row]').querySelector('[data-social-preview] i');
            if (preview) preview.className = e.target.value;
        });
    }

    document.addEventListener('click', function (e) {
        var add = e.target.closest('[data-social-add]');
        if (add) {
            var tpl = document.getElementById('social-row-template');
            socialsList.insertAdjacentHTML('beforeend', tpl.innerHTML);
            reindex(socialsList, 'meta[socials][__I__][__FIELD__]');
            return;
        }
        var rm = e.target.closest('[data-social-remove]');
        if (rm) {
            rm.closest('[data-social-row]').remove();
            reindex(socialsList, 'meta[socials][__I__][__FIELD__]');
        }
    });
})();
</script>
