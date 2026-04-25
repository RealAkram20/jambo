{{--
    Contact Us — structured admin fields. Rendered only when editing
    the contact-us page. Drives the public 4-card layout, sidebar, and
    map embed via the pages.meta JSON column.

    Defaults are populated by 2026_04_25_000300_seed_contact_page_meta.php
    so admins always start with sensible values.
--}}
@php
    $cards = old('meta.cards', $page->metaValue('cards', [
        ['title' => '', 'desc' => '', 'icon' => 'ph-headset', 'link_type' => 'email', 'link_value' => ''],
        ['title' => '', 'desc' => '', 'icon' => 'ph-phone-call', 'link_type' => 'phone', 'link_value' => ''],
        ['title' => '', 'desc' => '', 'icon' => 'ph-megaphone-simple', 'link_type' => 'email', 'link_value' => ''],
        ['title' => '', 'desc' => '', 'icon' => 'ph-newspaper-clipping', 'link_type' => 'email', 'link_value' => ''],
    ]));
    // Always render exactly 4 card slots — keeps the admin grid stable.
    while (count($cards) < 4) {
        $cards[] = ['title' => '', 'desc' => '', 'icon' => '', 'link_type' => 'email', 'link_value' => ''];
    }
@endphp

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Page heading</h6></div>
    <div class="card-body">
        <label for="meta_page_heading" class="form-label">Heading shown above the contact cards</label>
        <input type="text"
               class="form-control"
               id="meta_page_heading"
               name="meta[page_heading]"
               value="{{ old('meta.page_heading', $page->metaValue('page_heading')) }}"
               placeholder="e.g. Get in touch anytime">
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Contact cards (4 slots)</h6></div>
    <div class="card-body">
        <p class="text-secondary small mb-4">Each card shows an icon, a title, a short description, and a link (email, phone, or URL). Icons use <a href="https://phosphoricons.com/" target="_blank" class="text-decoration-none">Phosphor</a> names — e.g. <code>ph-headset</code>, <code>ph-phone-call</code>, <code>ph-megaphone-simple</code>.</p>

        <div class="row g-4">
            @foreach ($cards as $i => $card)
                <div class="col-lg-6">
                    <div class="border rounded p-3" style="background:rgba(255,255,255,0.02);">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary-subtle text-primary-emphasis">Card {{ $i + 1 }}</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-uppercase text-secondary">Icon (Phosphor name)</label>
                            <input type="text"
                                   class="form-control"
                                   name="meta[cards][{{ $i }}][icon]"
                                   value="{{ $card['icon'] ?? '' }}"
                                   placeholder="ph-headset">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-uppercase text-secondary">Title</label>
                            <input type="text"
                                   class="form-control"
                                   name="meta[cards][{{ $i }}][title]"
                                   value="{{ $card['title'] ?? '' }}"
                                   placeholder="Help &amp; support">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-uppercase text-secondary">Description</label>
                            <textarea class="form-control"
                                      rows="2"
                                      name="meta[cards][{{ $i }}][desc]"
                                      placeholder="Short blurb for this card.">{{ $card['desc'] ?? '' }}</textarea>
                        </div>

                        <div class="row g-2">
                            <div class="col-4">
                                <label class="form-label small text-uppercase text-secondary">Link type</label>
                                <select name="meta[cards][{{ $i }}][link_type]" class="form-select">
                                    <option value="email" @selected(($card['link_type'] ?? 'email') === 'email')>Email</option>
                                    <option value="phone" @selected(($card['link_type'] ?? '') === 'phone')>Phone</option>
                                    <option value="url" @selected(($card['link_type'] ?? '') === 'url')>URL</option>
                                </select>
                            </div>
                            <div class="col-8">
                                <label class="form-label small text-uppercase text-secondary">Link value</label>
                                <input type="text"
                                       class="form-control"
                                       name="meta[cards][{{ $i }}][link_value]"
                                       value="{{ $card['link_value'] ?? '' }}"
                                       placeholder="support@jambo.co">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Contact form</h6></div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Form heading</label>
            <input type="text" class="form-control"
                   name="meta[form_heading]"
                   value="{{ old('meta.form_heading', $page->metaValue('form_heading')) }}"
                   placeholder="Start the conversation">
        </div>

        <div class="mb-3">
            <label class="form-label">Subheading</label>
            <textarea class="form-control" rows="2"
                      name="meta[form_subheading]"
                      placeholder="Fill out the contact form, and one of our team members will be in touch shortly">{{ old('meta.form_subheading', $page->metaValue('form_subheading')) }}</textarea>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Recipient email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('meta.form_recipient_email') is-invalid @enderror"
                       name="meta[form_recipient_email]"
                       value="{{ old('meta.form_recipient_email', $page->metaValue('form_recipient_email')) }}"
                       placeholder="hello@jambo.co">
                <small class="text-secondary">Submissions are emailed here.</small>
                @error('meta.form_recipient_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Submit button label</label>
                <input type="text" class="form-control"
                       name="meta[form_button_label]"
                       value="{{ old('meta.form_button_label', $page->metaValue('form_button_label', 'Send Message')) }}"
                       placeholder="Send Message">
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Visit Us / Address</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Section heading</label>
                <input type="text" class="form-control"
                       name="meta[visit_us_heading]"
                       value="{{ old('meta.visit_us_heading', $page->metaValue('visit_us_heading', 'Visit Us')) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Address label</label>
                <input type="text" class="form-control"
                       name="meta[address_label]"
                       value="{{ old('meta.address_label', $page->metaValue('address_label', 'Address')) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Intro line</label>
                <input type="text" class="form-control"
                       name="meta[visit_us_intro]"
                       value="{{ old('meta.visit_us_intro', $page->metaValue('visit_us_intro')) }}"
                       placeholder="If you'd like to visit or write to us:">
            </div>
            <div class="col-12">
                <label class="form-label">Address (one line per row)</label>
                <textarea class="form-control" rows="4"
                          name="meta[address_lines]">{{ old('meta.address_lines', $page->metaValue('address_lines')) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Business inquiries</h6></div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Section heading</label>
            <input type="text" class="form-control"
                   name="meta[business_heading]"
                   value="{{ old('meta.business_heading', $page->metaValue('business_heading', 'Business Inquiries')) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Body</label>
            <textarea class="form-control" rows="3"
                      name="meta[business_body]">{{ old('meta.business_body', $page->metaValue('business_body')) }}</textarea>
        </div>
        <div>
            <label class="form-label">Email</label>
            <input type="email" class="form-control @error('meta.business_email') is-invalid @enderror"
                   name="meta[business_email]"
                   value="{{ old('meta.business_email', $page->metaValue('business_email')) }}"
                   placeholder="business@jambo.co">
            @error('meta.business_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Follow Us</h6></div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Section label</label>
            <input type="text" class="form-control"
                   name="meta[follow_label]"
                   value="{{ old('meta.follow_label', $page->metaValue('follow_label', 'Follow Us')) }}">
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Facebook URL</label>
                <input type="url" class="form-control @error('meta.facebook_url') is-invalid @enderror"
                       name="meta[facebook_url]"
                       value="{{ old('meta.facebook_url', $page->metaValue('facebook_url')) }}"
                       placeholder="https://facebook.com/...">
                @error('meta.facebook_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">X (Twitter) URL</label>
                <input type="url" class="form-control @error('meta.twitter_url') is-invalid @enderror"
                       name="meta[twitter_url]"
                       value="{{ old('meta.twitter_url', $page->metaValue('twitter_url')) }}"
                       placeholder="https://twitter.com/...">
                @error('meta.twitter_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">YouTube URL</label>
                <input type="url" class="form-control @error('meta.youtube_url') is-invalid @enderror"
                       name="meta[youtube_url]"
                       value="{{ old('meta.youtube_url', $page->metaValue('youtube_url')) }}"
                       placeholder="https://youtube.com/...">
                @error('meta.youtube_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <p class="text-secondary small mt-3 mb-0">Leave a URL blank to hide that social icon.</p>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Map</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-9">
                <label class="form-label">Google Maps embed URL</label>
                <input type="url" class="form-control @error('meta.map_embed_url') is-invalid @enderror"
                       name="meta[map_embed_url]"
                       value="{{ old('meta.map_embed_url', $page->metaValue('map_embed_url')) }}"
                       placeholder="https://www.google.com/maps/embed?pb=...">
                <small class="text-secondary">From Google Maps → Share → Embed a map → copy the <code>src</code> from the iframe.</small>
                @error('meta.map_embed_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Height (px)</label>
                <input type="number" class="form-control"
                       name="meta[map_height]"
                       value="{{ old('meta.map_height', $page->metaValue('map_height', 600)) }}"
                       min="100" max="1200">
            </div>
        </div>
        <p class="text-secondary small mt-3 mb-0">Leave the URL blank to hide the map entirely.</p>
    </div>
</div>
