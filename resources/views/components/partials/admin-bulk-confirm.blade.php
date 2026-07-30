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
    // NOTE: do NOT gate anything below on `typeof Swal` at parse time.
    // The layout loads sweetalert2 with `async`, so this inline script
    // almost always runs BEFORE Swal exists. An early return here used to
    // skip the bulk-selection wiring entirely — select-all appeared to
    // toggle (it's a native checkbox) while no rows ticked and the action
    // bar never opened. Swal is only needed once someone clicks a submit,
    // which is long after the async load settles, so it's resolved lazily
    // in confirmVia() with a native-confirm fallback.

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
        // Plan assignment is a change, not a destruction — question icon and
        // the primary button colour, so it doesn't read like a delete.
        'bulk-tier-movies': function (form) {
            var n = selectedCount(form);
            var plan = selectedPlan(form);
            return {
                title: 'Set ' + n + ' movie' + (n === '1' ? '' : 's') + ' to ' + plan.label + '?',
                text: plan.isFree
                    ? 'These movies become free to watch for everyone, including visitors who are not signed in.'
                    : 'Only subscribers on ' + plan.label + ' (or higher) will be able to watch these movies.',
                confirmText: 'Yes, apply plan',
                icon: 'question',
                confirmColor: '#0d6efd',
            };
        },
        'bulk-tier-episodes': function (form) {
            var n = selectedCount(form);
            var plan = selectedPlan(form);
            return {
                title: 'Set ' + n + ' episode' + (n === '1' ? '' : 's') + ' to ' + plan.label + '?',
                text: plan.isFree
                    ? 'These episodes become free to watch for everyone, including visitors who are not signed in.'
                    : 'Only subscribers on ' + plan.label + ' (or higher) will be able to watch these episodes. This overrides the plan they inherit from the series.',
                confirmText: 'Yes, apply plan',
                icon: 'question',
                confirmColor: '#0d6efd',
            };
        },
        'bulk-tier-series': function (form) {
            var n = selectedCount(form);
            var plan = selectedPlan(form);
            return {
                title: 'Set ' + n + ' series to ' + plan.label + '?',
                text: plan.isFree
                    ? 'These series and every episode inside them become free to watch for everyone, including visitors who are not signed in.'
                    : 'This applies to every episode in the selected series too, so the whole run is gated at ' + plan.label + '.',
                confirmText: 'Yes, apply plan',
                icon: 'question',
                confirmColor: '#0d6efd',
            };
        },
    };

    // The count lives in the bulk bar, which is a sibling of the form rather
    // than an ancestor — so look it up on the shared scope container.
    function selectedCount(form) {
        var scope = form.closest('[data-bulk-scope]');
        var el = scope && scope.querySelector('[id$="-bulk-count"]');
        return (el && el.textContent) || '0';
    }

    function selectedPlan(form) {
        var select = form.querySelector('select[name="tier_required"]');
        if (!select || select.selectedIndex < 0) {
            return { label: 'Free', isFree: true };
        }
        var opt = select.options[select.selectedIndex];
        return {
            label: opt.dataset.planLabel || opt.textContent.trim(),
            isFree: opt.dataset.planFree === '1',
        };
    }

    function copyFor(form) {
        var key = form.dataset.jamboConfirm;

        return (COPY[key] || function () {
            return { title: 'Are you sure?', text: '', confirmText: 'Yes' };
        })(form);
    }

    /**
     * Ask for confirmation, resolving to true/false.
     *
     * Swal is looked up here rather than at script load because the vendor
     * bundle is loaded `async`. If it still hasn't arrived by the time the
     * admin clicks, a native confirm carries the same copy — the action
     * stays usable instead of silently doing nothing.
     */
    function confirmVia(form) {
        var copy = copyFor(form);

        if (typeof Swal === 'undefined') {
            var text = copy.text ? copy.title + '\n\n' + copy.text : copy.title;
            return Promise.resolve(window.confirm(text));
        }

        return Swal.fire({
            title: copy.title,
            text: copy.text,
            icon: copy.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: copy.confirmText,
            cancelButtonText: 'Cancel',
            confirmButtonColor: copy.confirmColor || '#dc3545',
            cancelButtonColor: '#6c757d',
            background: '#10131c',
            color: '#fff',
            buttonsStyling: true,
            reverseButtons: true,
        }).then(function (res) {
            return !!res.isConfirmed;
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-jambo-confirm]');
        if (!form) return;
        // Already passed the confirm gate (set by the resolved branch).
        if (form.dataset.jamboConfirmed === '1') return;
        e.preventDefault();
        confirmVia(form).then(function (confirmed) {
            if (confirmed) {
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

        // A bulk bar can host several actions (delete, assign plan, ...), so
        // the selected ids have to be mirrored into every action's form —
        // not just the one legacy `#<scope>-bulk-ids` container. Any element
        // tagged data-bulk-ids="<scope>" receives its own copy of the
        // hidden ids[] inputs.
        var idsHolders = Array.prototype.slice.call(
            document.querySelectorAll('[data-bulk-ids="' + scope + '"]')
        );
        var legacyHolder = document.getElementById(scope + '-bulk-ids');
        if (legacyHolder && idsHolders.indexOf(legacyHolder) === -1) {
            idsHolders.push(legacyHolder);
        }

        if (!selectAll || !bar || !countEl || !idsHolders.length) return;

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
            // Rebuild hidden ids[] inputs on every bulk form in this scope.
            idsHolders.forEach(function (holder) {
                holder.innerHTML = '';
                checked.forEach(function (cb) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    holder.appendChild(input);
                });
            });

            // Apply depends on the selection as well as the chosen plan, so
            // it has to re-evaluate whenever the selection changes.
            syncPlanPickers(scope);
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

    // ----- Plan picker --------------------------------------------------
    // Apply stays inert until BOTH conditions hold: a plan is chosen, and at
    // least one row is selected. The endpoint requires `ids` and a non-empty
    // `tier_required`, so a greyed-out button states the requirement up front
    // instead of bouncing the admin through a validation redirect.
    function planForms(scope) {
        var selector = 'form[data-jambo-confirm^="bulk-tier-"]';
        var root = scope
            ? document.querySelector('[data-bulk-scope="' + scope + '"]')
            : document;

        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function syncPlanPickers(scope) {
        planForms(scope).forEach(function (form) {
            var select = form.querySelector('select[name="tier_required"]');
            var button = form.querySelector('button[type="submit"]');
            if (!select || !button) return;

            var hasSelection = form.querySelectorAll('input[name="ids[]"]').length > 0;
            button.disabled = !select.value || !hasSelection;
        });
    }

    function wirePlanPickers() {
        planForms(null).forEach(function (form) {
            var select = form.querySelector('select[name="tier_required"]');
            if (!select) return;

            select.addEventListener('change', function () {
                var host = form.closest('[data-bulk-scope]');
                syncPlanPickers(host ? host.dataset.bulkScope : null);
            });
        });

        syncPlanPickers(null);
    }

    function boot() {
        // Look for any scopes declared on the page.
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-bulk-scope]'),
            function (el) { wireBulk(el.dataset.bulkScope); }
        );

        wirePlanPickers();
    }

    // The partial normally sits after the table, but don't depend on it —
    // if it's ever included higher up, the selectors above would find
    // nothing and the bar would go dead with no visible error.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
