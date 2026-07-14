{{--
    Single source of truth for Jambo's brand / theme CSS custom properties.
    Included in the <head> of every layout (admin, partner, user, auth, frontend).

    DO NOT re-declare these :root variables inline in any layout — edit them here.
    Changing --bs-primary here re-skins the entire app.
--}}
<style>
    :root {
        /* Brand primary (blue) */
        --bs-primary: #1A98FF;
        --bs-primary-rgb: 26, 152, 255;
        --bs-link-color: #1A98FF;
        --bs-link-color-rgb: 26, 152, 255;
        --bs-link-hover-color: #147acc;

        /* Brand accents (Streamit "color-2" scheme) */
        --brand-accent: #89F425;   /* green — charts/highlights */
        --brand-muted: #adafb8;    /* secondary text */

        /* Dark-panel scale — use these instead of hardcoding
           #0b0f17 / #141923 / #1f2738 in individual views. */
        --panel-1: #0b0f17;
        --panel-2: #141923;
        --panel-3: #1f2738;
    }

    /* --------------------------------------------------------------
       Typography utilities — replace the inline style="font-size:Npx"
       literals copy-pasted across admin/partner/user views.
       -------------------------------------------------------------- */
    .text-eyebrow {          /* uppercase table heads / KPI labels */
        font-size: 11px;
        letter-spacing: .5px;
        text-transform: uppercase;
        color: var(--bs-secondary);
    }
    .text-meta   { font-size: 12px; }   /* secondary captions */
    .text-meta-sm{ font-size: 11px; }
    .text-body-sm{ font-size: 13px; }   /* dense body / subtitles */
    .stat-value  { font-size: 24px; font-weight: 600; line-height: 1.2; }

    /* --------------------------------------------------------------
       Sidebar section grouping (admin + partner shells, hub rail).
       The Streamit theme colors .static-item .default-icon with
       --bs-gray-900, which is near-invisible in dark mode — override
       with the brand muted tone so group labels actually read.
       .sidenav-divider is the theme-aware rule drawn above each
       group header (the theme's .hr-horizontal is a black gradient
       that vanishes on dark backgrounds).
       -------------------------------------------------------------- */
    .sidebar .nav-item.static-item .default-icon {
        color: var(--brand-muted);
        font-size: 11px;
        letter-spacing: .18rem;
    }
    .sidenav-divider {
        border: 0;
        height: 1px;
        margin: .5rem 1rem .25rem 0;
        background: var(--bs-border-color, rgba(255, 255, 255, .1));
        opacity: 1;
    }
</style>
