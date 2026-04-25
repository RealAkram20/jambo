{{-- Shared form partial for create + edit --}}
@csrf

@php
    // Pages whose body is fully structured via the meta JSON column —
    // they have their own admin partial (cards, Q&A list, etc.) and
    // don't need the generic Quill body field.
    $structuredPages = ['contact-us', 'faqs', 'footer'];
    $isStructured = in_array($page->slug, $structuredPages, true);

    // Pages that aren't reachable as a public URL — hide the slug,
    // featured image, SEO, "View" button, and meta description from
    // the form because they're meaningless for these.
    $isInternalOnly = $page->slug === 'footer';
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Content</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $page->title) }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if (! $isStructured)
                    <div class="mb-3">
                        <label for="quill-editor" class="form-label">Body</label>
                        {{-- Quill renders into #quill-editor; the JS below copies
                             the rendered HTML into the hidden `content` input
                             on form submit. Toolbar matches the screenshot:
                             heading dropdown, B/I/U, colour, link, blockquote,
                             lists, alignment, clear formatting, code-view. --}}
                        <div id="quill-toolbar">
                            <span class="ql-formats">
                                <select class="ql-header">
                                    <option selected>Normal</option>
                                    <option value="1">Heading 1</option>
                                    <option value="2">Heading 2</option>
                                    <option value="3">Heading 3</option>
                                </select>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-bold" type="button"></button>
                                <button class="ql-italic" type="button"></button>
                                <button class="ql-underline" type="button"></button>
                                <select class="ql-color"></select>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-link" type="button"></button>
                                <button class="ql-blockquote" type="button"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered" type="button"></button>
                                <button class="ql-list" value="bullet" type="button"></button>
                                <select class="ql-align"></select>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean" type="button"></button>
                                <button class="ql-code-block" type="button"></button>
                            </span>
                        </div>
                        <div id="quill-editor" style="min-height: 320px;">{!! old('content', $page->content) !!}</div>
                        <input type="hidden" name="content" id="content" value="{{ old('content', $page->content) }}">
                        <small class="text-secondary">Share your memories with text, photos, videos, or documents.</small>
                        @error('content') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                @endif
            </div>
        </div>

        @if ($page->slug === 'contact-us')
            @include('pages::admin.pages.partials.contact-fields')
        @elseif ($page->slug === 'faqs')
            @include('pages::admin.pages.partials.faq-fields')
        @elseif ($page->slug === 'footer')
            @include('pages::admin.pages.partials.footer-fields')
        @endif

        @if (! $isInternalOnly)
            <div class="card mt-4">
                <div class="card-header"><h6 class="mb-0">Featured image</h6></div>
                <div class="card-body">
                    @include('content::admin.partials.media-picker-field', [
                        'key' => 'featured_image_url',
                        'label' => 'Image',
                        'value' => old('featured_image_url', $page->featured_image_url),
                        'accept' => ['jpg','jpeg','png','webp','svg'],
                        'aspect' => '16/9',
                        'placeholder' => 'https://... or /storage/media/pages/...',
                        'hint' => 'Optional. Shown above the page title on the public view.',
                    ])
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h6 class="mb-0">SEO</h6></div>
                <div class="card-body">
                    <div class="mb-0">
                        <label for="meta_description" class="form-label">Meta description</label>
                        <textarea class="form-control @error('meta_description') is-invalid @enderror" id="meta_description" name="meta_description" rows="2" maxlength="500" placeholder="One or two sentences shown by search engines.">{{ old('meta_description', $page->meta_description) }}</textarea>
                        <small class="text-secondary">Up to 500 characters. Falls back to the title if blank.</small>
                        @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Publishing</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="published" @selected(old('status', $page->status ?: 'published') === 'published')>Published</option>
                        <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                    </select>
                    <small class="text-secondary">Published pages show your content on the public site. Drafts fall back to the legacy template.</small>
                </div>

                @if (! $isInternalOnly)
                    <div class="mb-0">
                        <label for="slug" class="form-label">Slug</label>
                        <div class="input-group">
                            <span class="input-group-text">/</span>
                            <input type="text"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   id="slug" name="slug"
                                   value="{{ old('slug', $page->slug) }}"
                                   @if ($page->exists && $page->is_system) readonly @endif
                                   placeholder="auto-generated from title">
                        </div>
                        @if ($page->exists && $page->is_system)
                            <small class="text-secondary">System page slug is locked — public URLs would 404 if changed.</small>
                        @else
                            <small class="text-secondary">Letters, numbers, dashes, underscores. Leave blank to auto-generate.</small>
                        @endif
                        @error('slug') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost">← Back to list</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> {{ $page->exists ? 'Save page' : 'Publish' }}
    </button>
</div>

<script>
(function () {
    function initQuill() {
        if (typeof Quill === 'undefined') return setTimeout(initQuill, 50);
        var holder = document.getElementById('quill-editor');
        if (!holder || holder.__quill) return;

        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Share your memories with text, photos, videos, or documents.',
            modules: {
                toolbar: '#quill-toolbar',
            },
        });
        holder.__quill = quill;

        var hidden = document.getElementById('content');
        var form = holder.closest('form');
        form.addEventListener('submit', function () {
            var html = quill.root.innerHTML.trim();
            // Treat an empty editor (Quill leaves <p><br></p>) as null.
            hidden.value = html === '<p><br></p>' ? '' : html;
        });
    }
    initQuill();
})();
</script>
@include('content::admin.partials.media-picker-script')
