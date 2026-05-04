{{--
    Shared helpers for the AJAX cast picker used on movie + show
    forms. Includes:

      1. Select2 stylesheet link (admin layout doesn't load it
         globally; this is the only place it's needed today).
      2. The "+ New person" Bootstrap modal — single instance shared
         across every cast row on the page.
      3. The JS that wires up Select2 AJAX search on existing rows,
         re-initialises it for newly-added rows, and handles the
         quick-create modal flow.

    Usage from a form: include this partial once, somewhere after
    the cast-rows container and the cast-row-template definition.
    Then call `window.jamboInitCastPicker('#cast-rows')` when adding
    new rows so Select2 attaches to the new <select>.
--}}

<link rel="stylesheet" href="{{ asset('dashboard/vendor/select2/dist/css/select2.min.css') }}">

<style>
    /* Make Select2 honour Bootstrap's input-group sizing alongside
       the "+ New person" button next to each picker. Default Select2
       width:auto would collapse the field; flex-fill restores it. */
    .input-group > .select2-container { flex: 1 1 auto; width: 1% !important; min-width: 0; }
    .input-group > .select2-container .select2-selection--single {
        height: calc(1.5em + 0.5rem + 2px); /* match form-select-sm */
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-top: 0.25rem;
    }
    /* Dark-theme dropdown to match the admin chrome. */
    .select2-dropdown {
        background: #0f1421;
        border-color: #1f2738;
        color: #e6e9ef;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted {
        background-color: #1A98FF;
    }
    .select2-search--dropdown .select2-search__field {
        background: #0b0f17;
        color: #e6e9ef;
        border-color: #1f2738;
    }

    /* z-index — Select2's default 1051 loses to the sticky admin
       header (~1020) and Bootstrap dropdowns/popovers, and most
       importantly to the publishing-sidebar card column which lives
       in a side flex track that visually overlaps the dropdown
       panel. Bump everything above the modal layer (1055-1060) so
       the open dropdown is always on top of admin chrome. */
    .select2-container--open,
    .select2-container--open .select2-dropdown {
        z-index: 1080;
    }
    /* When Select2 is rendered inside an open Bootstrap modal
       (future use — quick-create modal currently uses plain inputs)
       Bootstrap modals are 1060, so we bump the in-modal Select2
       higher still. */
    .modal.show .select2-container--open,
    .modal.show .select2-container--open .select2-dropdown {
        z-index: 1090;
    }
</style>

{{-- Quick-create modal — single instance per page; the active row
     is remembered by the helper JS so the new person lands in the
     correct cast slot when the modal saves. --}}
<div class="modal fade" id="jambo-quick-person-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="jambo-quick-person-form" class="modal-content" autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title">Add new person</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" required maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" required maxlength="255">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Known for <small class="text-muted">(optional)</small></label>
                        <input type="text" class="form-control" name="known_for" maxlength="255"
                               placeholder="e.g. Inception, The Dark Knight">
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    You can add a photo, bio and other details later from the
                    <a href="{{ route('admin.persons.index') }}" target="_blank" rel="noopener">Persons admin</a>.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ph ph-plus me-1"></i> Add person
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* The Vite app.js bundle (which ships jQuery + Select2) is loaded
   as a deferred ES module, so it executes AFTER this inline script
   runs — meaning a synchronous Select2 check at parse time would
   always bail out, even though Select2 is on its way. Poll briefly
   for jQuery + $.fn.select2 before initialising. */
(function () {
    'use strict';

    var ATTEMPTS = 0;
    var MAX_ATTEMPTS = 60; // 60 * 100ms = 6s ceiling — well past defer load

    function ready() {
        return window.jQuery
            && window.jQuery.fn
            && window.jQuery.fn.select2
            && window.bootstrap; // also need Bootstrap for the modal
    }

    function waitAndInit() {
        if (ready()) {
            initCastPicker(window.jQuery);
            return;
        }
        if (ATTEMPTS++ < MAX_ATTEMPTS) {
            setTimeout(waitAndInit, 100);
        } else {
            console.warn('[cast-picker] jQuery/Select2/Bootstrap not ready after 6s — falling back to plain <select>.');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitAndInit);
    } else {
        waitAndInit();
    }
})();

function initCastPicker($) {
    'use strict';

    var personsSearchUrl = @json(route('admin.persons.search'));
    var personsQuickUrl  = @json(route('admin.persons.quick'));
    var csrf = $('meta[name=csrf-token]').attr('content') || '';

    function templateResult(item) {
        if (item.loading) return item.text;
        if (!item.id) return item.text;

        var $row = $('<div></div>').addClass('d-flex align-items-center gap-2');
        if (item.photo_url) {
            $row.append(
                $('<img>').attr('src', item.photo_url)
                    .css({width: '24px', height: '24px', borderRadius: '50%', objectFit: 'cover', flexShrink: 0})
            );
        } else {
            $row.append(
                $('<div></div>')
                    .css({width: '24px', height: '24px', borderRadius: '50%', background: '#1f2738',
                        flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px'})
                    .html('<i class="ph ph-user"></i>')
            );
        }
        var $info = $('<div></div>').css('min-width', 0);
        $info.append($('<div></div>').text(item.text));
        if (item.known_for) {
            $info.append($('<small></small>').addClass('text-muted').text(item.known_for));
        }
        $row.append($info);
        return $row;
    }

    function initSelect2(scope) {
        var $scope = $(scope || document);
        $scope.find('.jambo-cast-person').each(function () {
            if (this.classList.contains('select2-hidden-accessible')) return;
            $(this).select2({
                placeholder: this.dataset.placeholder || 'Search…',
                width: '100%',
                allowClear: true,
                // Render the dropdown as a child of <body> rather than
                // inside the cast-row's input-group. The card-body /
                // input-group ancestors clip overflow, which made the
                // dropdown panel render but be invisible (the user just
                // saw an empty Select2 trigger they couldn't search in).
                // Body-attached + z-index 1080 escapes every clip.
                dropdownParent: $('body'),
                ajax: {
                    url: personsSearchUrl,
                    dataType: 'json',
                    delay: 200,
                    data: function (params) {
                        return { q: params.term || '', page: params.page || 1 };
                    },
                    processResults: function (data) {
                        return { results: data.results, pagination: data.pagination };
                    },
                    cache: true,
                },
                minimumInputLength: 0,
                templateResult: templateResult,
                templateSelection: function (item) {
                    return item.text || item.id || '';
                },
            });
        });
    }

    /* "+ New person" modal flow ------------------------------------ */
    var modalEl = document.getElementById('jambo-quick-person-modal');
    var modalInstance = null;
    var $form = $('#jambo-quick-person-form');
    var activeSelect = null;

    if (modalEl && window.bootstrap) {
        modalInstance = new window.bootstrap.Modal(modalEl);
    }

    $(document).on('click', '[data-jambo-new-person]', function () {
        var rowEl = this.closest('.cast-row');
        if (!rowEl) return;
        activeSelect = rowEl.querySelector('.jambo-cast-person');
        // Reset modal form between opens so previous values don't
        // leak when the admin adds several people in a row.
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        if (modalInstance) modalInstance.show();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        var $btn = $form.find('button[type=submit]');
        $btn.prop('disabled', true);

        $.ajax({
            url: personsQuickUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            data: {
                first_name: $form.find('[name=first_name]').val(),
                last_name: $form.find('[name=last_name]').val(),
                known_for: $form.find('[name=known_for]').val(),
            },
        }).done(function (data) {
            if (data && data.person && activeSelect) {
                // Append the new option and select it. trigger('change')
                // makes Select2 re-render the selected display.
                var opt = new Option(data.person.text, data.person.id, true, true);
                $(activeSelect).append(opt).trigger('change');
            }
            if (modalInstance) modalInstance.hide();
        }).fail(function (xhr) {
            var errs = (xhr.responseJSON && xhr.responseJSON.errors) || {};
            $form.find('input[name]').each(function () {
                var name = this.name;
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
                if (errs[name] && errs[name][0]) {
                    $(this).addClass('is-invalid');
                    $('<div class="invalid-feedback"></div>').text(errs[name][0]).insertAfter(this);
                }
            });
            if (!Object.keys(errs).length) {
                alert('Could not create person. Please try again.');
            }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* Public entrypoint for the per-form scripts ------------------- */
    window.jamboInitCastPicker = function (scope) {
        initSelect2(scope);
    };

    // Init on initial page load — we're already past DOMContentLoaded
    // by the time waitAndInit runs us, so call directly.
    initSelect2('#cast-rows');
}
</script>
