{{--
    Publishing clock, shared by the movie / show / episode forms.

    `datetime-local` posts a bare wall-clock string with no timezone, so the
    server has to be told once and for all what it means. It means EAT — the
    audience's clock — no matter where the admin is sitting. This partial is
    what makes that safe rather than merely assumed:

      * Every "now" prefill is computed in EAT, NOT in the browser's local
        time. This is the sharp edge: an admin in Dubai or Tokyo hitting
        Published would otherwise prefill their own wall clock, which the
        server then reads as EAT and stores hours in the future — the title
        goes invisible and its announcement is withheld. Same bug as before,
        different admin.
      * An admin whose own timezone differs from EAT is shown their local
        equivalent, so nobody has to do the arithmetic in their head.
--}}
<script>
(function () {
    const ZONE = @json(\App\Support\LocalTime::timezone());
    const ABBR = @json(\App\Support\LocalTime::abbreviation());

    const input = document.getElementById('published_at');
    const hint  = document.querySelector('[data-jambo-tz-hint]');
    if (!input) return;

    // "Now", expressed on Jambo's clock rather than this browser's.
    // en-CA gives ISO-ish YYYY-MM-DD, which datetime-local wants.
    window.jamboNowForInput = function () {
        const parts = new Intl.DateTimeFormat('en-CA', {
            timeZone: ZONE, hour12: false,
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
        }).formatToParts(new Date()).reduce((acc, p) => (acc[p.type] = p.value, acc), {});

        // Intl renders midnight as "24" in some engines; datetime-local wants "00".
        const hour = parts.hour === '24' ? '00' : parts.hour;

        return `${parts.year}-${parts.month}-${parts.day}T${hour}:${parts.minute}`;
    };

    // Translate the EAT wall clock the admin typed into their own, but only
    // when they actually differ — a Kampala admin doesn't need to be told
    // that 8pm is 8pm.
    const viewerZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (!hint || viewerZone === ZONE) return;

    function renderHint() {
        if (!input.value) { hint.textContent = ''; return; }

        // The typed value is EAT. Reconstruct the real instant by asking what
        // offset EAT was at that moment, then re-render it in the admin's zone.
        const asIfUtc = new Date(input.value + 'Z');
        const offsetMs = asIfUtc - new Date(asIfUtc.toLocaleString('en-US', { timeZone: ZONE }));
        const instant = new Date(asIfUtc.getTime() + offsetMs);

        if (isNaN(instant)) { hint.textContent = ''; return; }

        hint.textContent = instant.toLocaleString([], {
            dateStyle: 'medium', timeStyle: 'short',
        }) + ' your time (' + viewerZone + ')';
    }

    input.addEventListener('input', renderHint);
    input.addEventListener('change', renderHint);
    renderHint();
})();
</script>
