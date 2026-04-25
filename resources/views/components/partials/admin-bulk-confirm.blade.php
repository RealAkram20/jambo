{{--
    Shared admin-table bulk-action + SweetAlert2 confirm helper.

    Two responsibilities:

      1. Hijack any form tagged `data-jambo-confirm="<key>"` so submit
         goes through SweetAlert2 instead of the native confirm() dialog.
         Copy is keyed on `data-jambo-confirm` so each context can show
         tailored wording.

      2. Bulk-select wiring for any list page that follows the
         convention:
            - row checkboxes class `<scope>-row-cb`
            - select-all checkbox id `<scope>-select-all`
            - bulk bar id `<scope>-bulk-bar` (toggled .d-none)
            - count span id `<scope>-bulk-count`
            - hidden ids container id `<scope>-bulk-ids`
            - bulk form id `<scope>-bulk-form`
         The scope is auto-detected from any element with
         `data-bulk-scope` on the page.
--}}
<script>
(function () {
    if (typeof Swal === 'undefined') {
        // SweetAlert2 not loaded — fall back to native confirm so the
        // page still works. Layout passes isSweetalert => true on the
        // pages that include this partial; this guard catches mistakes.
        document.addEventListener('submit', function (e) {
            var form = e.target.closest('form[data-jambo-confirm]');
            if (!form) return;
            if (!window.confirm('Are you sure?')) e.preventDefault();
        });
        return;
    }

    var COPY = {
        'delete-movie': function (form) {
            var title = form.dataset.title || 'this movie';
            return {
                title: 'Delete this movie?',
                text: '"' + title + '" will be removed permanently. This cannot be undone.',
                confirmText: 'Yes, delete',
            };
        },
        'delete-series': function (form) {
            var title = form.dataset.title || 'this series';
            return {
                title: 'Delete this series?',
                text: '"' + title + '" plus its seasons and episodes will be removed permanently. This cannot be undone.',
                confirmText: 'Yes, delete',
            };
        },
        'bulk-delete-movies': function (form) {
            var n = (form.querySelector('[id$="-bulk-count"]') || {}).textContent || '0';
            return {
                title: 'Delete ' + n + ' selected movie' + (n === '1' ? '' : 's') + '?',
                text: 'These movies will be removed permanently. This cannot be undone.',
                confirmText: 'Yes, delete all',
            };
        },
        'bulk-delete-series': function (form) {
            var n = (form.querySelector('[id$="-bulk-count"]') || {}).textContent || '0';
            return {
                title: 'Delete ' + n + ' selected series?',
                text: 'These series plus their seasons and episodes will be removed permanently. This cannot be undone.',
                confirmText: 'Yes, delete all',
            };
        },
    };

    function fire(form) {
        var key = form.dataset.jamboConfirm;
        var copy = (COPY[key] || function () {
            return { title: 'Are you sure?', text: '', confirmText: 'Yes' };
        })(form);

        return Swal.fire({
            title: copy.title,
            text: copy.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: copy.confirmText,
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            background: '#10131c',
            color: '#fff',
            buttonsStyling: true,
            reverseButtons: true,
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-jambo-confirm]');
        if (!form) return;
        // Already passed the confirm gate (set by the resolved branch).
        if (form.dataset.jamboConfirmed === '1') return;
        e.preventDefault();
        fire(form).then(function (res) {
            if (res.isConfirmed) {
                form.dataset.jamboConfirmed = '1';
                form.submit();
            }
        });
    });

    // ----- Bulk selection wiring ---------------------------------------
    function wireBulk(scope) {
        var selectAll = document.getElementById(scope + '-select-all');
        var bar       = document.getElementById(scope + '-bulk-bar');
        var countEl   = document.getElementById(scope + '-bulk-count');
        var idsHolder = document.getElementById(scope + '-bulk-ids');
        var form      = document.getElementById(scope + '-bulk-form');
        if (!selectAll || !bar || !countEl || !idsHolder || !form) return;

        var rows = function () {
            return Array.prototype.slice.call(
                document.querySelectorAll('.' + scope + '-row-cb')
            );
        };

        function refresh() {
            var checked = rows().filter(function (cb) { return cb.checked; });
            countEl.textContent = String(checked.length);
            if (checked.length > 0) {
                bar.classList.remove('d-none');
                bar.classList.add('d-flex');
            } else {
                bar.classList.add('d-none');
                bar.classList.remove('d-flex');
            }
            // Sync the select-all visual state.
            var all = rows();
            if (all.length && checked.length === all.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else if (checked.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
            // Rebuild hidden ids[] inputs on the bulk form.
            idsHolder.innerHTML = '';
            checked.forEach(function (cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                idsHolder.appendChild(input);
            });
        }

        selectAll.addEventListener('change', function () {
            rows().forEach(function (cb) { cb.checked = selectAll.checked; });
            refresh();
        });
        document.addEventListener('change', function (e) {
            if (e.target && e.target.matches('.' + scope + '-row-cb')) refresh();
        });

        refresh();
    }

    // Look for any scopes declared on the page.
    Array.prototype.forEach.call(
        document.querySelectorAll('[data-bulk-scope]'),
        function (el) { wireBulk(el.dataset.bulkScope); }
    );
})();
</script>
