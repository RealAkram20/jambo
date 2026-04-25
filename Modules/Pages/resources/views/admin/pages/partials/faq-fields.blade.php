{{--
    FAQ — structured admin fields. Rendered only when editing the
    faqs page. Drives the public accordion via the pages.meta JSON
    column (`questions` array). Add/remove rows on the fly; an empty
    question + answer is treated as deleted on save.
--}}
@php
    $questions = old('meta.questions', $page->metaValue('questions', []));
    if (empty($questions)) {
        $questions = [['q' => '', 'a' => '']];
    }
@endphp

<div class="card mt-4" id="faq-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Frequently asked questions</h6>
        <button type="button" class="btn btn-sm btn-primary" id="faq-add">
            <i class="ph ph-plus me-1"></i> Add question
        </button>
    </div>
    <div class="card-body">
        <p class="text-secondary small mb-4">Each entry becomes one collapsible row in the public accordion. Leave both fields blank and the row is dropped on save.</p>

        <div id="faq-rows">
            @foreach ($questions as $i => $row)
                <div class="border rounded p-3 mb-3 faq-row" style="background:rgba(255,255,255,0.02);" data-faq-row>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary-subtle text-primary-emphasis" data-faq-index>Q{{ $i + 1 }}</span>
                        <button type="button" class="btn btn-sm btn-danger-subtle" data-faq-remove title="Remove this question">
                            <i class="ph ph-trash-simple"></i>
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-uppercase text-secondary">Question</label>
                        <input type="text"
                               class="form-control"
                               name="meta[questions][{{ $i }}][q]"
                               value="{{ $row['q'] ?? '' }}"
                               placeholder="What Is Jambo?">
                    </div>

                    <div>
                        <label class="form-label small text-uppercase text-secondary">Answer</label>
                        <textarea class="form-control"
                                  rows="4"
                                  name="meta[questions][{{ $i }}][a]"
                                  placeholder="Plain text — line breaks are preserved on the public page.">{{ $row['a'] ?? '' }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-ghost w-100 mt-2" id="faq-add-bottom">
            <i class="ph ph-plus me-1"></i> Add another question
        </button>
    </div>
</div>

<template id="faq-row-template">
    <div class="border rounded p-3 mb-3 faq-row" style="background:rgba(255,255,255,0.02);" data-faq-row>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-primary-subtle text-primary-emphasis" data-faq-index>Q__N__</span>
            <button type="button" class="btn btn-sm btn-danger-subtle" data-faq-remove title="Remove this question">
                <i class="ph ph-trash-simple"></i>
            </button>
        </div>

        <div class="mb-3">
            <label class="form-label small text-uppercase text-secondary">Question</label>
            <input type="text"
                   class="form-control"
                   name="meta[questions][__I__][q]"
                   value=""
                   placeholder="What Is Jambo?">
        </div>

        <div>
            <label class="form-label small text-uppercase text-secondary">Answer</label>
            <textarea class="form-control"
                      rows="4"
                      name="meta[questions][__I__][a]"
                      placeholder="Plain text — line breaks are preserved on the public page."></textarea>
        </div>
    </div>
</template>

<script>
(function () {
    var rows = document.getElementById('faq-rows');
    var tpl = document.getElementById('faq-row-template');
    if (!rows || !tpl) return;

    // Stable monotonically-increasing index for new rows. Using the
    // current count as a base means a fresh add doesn't collide with
    // existing keys, even after the user has removed earlier rows.
    var nextIndex = rows.querySelectorAll('[data-faq-row]').length;

    function relabel() {
        rows.querySelectorAll('[data-faq-row]').forEach(function (row, idx) {
            var label = row.querySelector('[data-faq-index]');
            if (label) label.textContent = 'Q' + (idx + 1);
        });
    }

    function addRow() {
        var html = tpl.innerHTML
            .replaceAll('__I__', String(nextIndex))
            .replaceAll('__N__', String(nextIndex + 1));
        nextIndex++;
        rows.insertAdjacentHTML('beforeend', html);
        relabel();
    }

    document.getElementById('faq-add').addEventListener('click', addRow);
    document.getElementById('faq-add-bottom').addEventListener('click', addRow);

    rows.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-faq-remove]');
        if (!btn) return;
        var row = btn.closest('[data-faq-row]');
        if (!row) return;
        // Don't let admin nuke the last visible row — it'd leave the
        // FAQ page empty with no obvious way to add one back without
        // refreshing.
        if (rows.querySelectorAll('[data-faq-row]').length <= 1) {
            row.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
            return;
        }
        row.remove();
        relabel();
    });
})();
</script>
