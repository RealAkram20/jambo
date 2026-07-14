# Jambo UI Superpowers — Skills, MCP & Component Kit

Everything here is **free**. Three layers: (1) Claude skills, (2) MCP servers, (3) the Blade UI kit.

---

## 1. Skills (already installed in Claude Code)

Invoke inside any Claude Code chat:

| Skill | When | How |
|---|---|---|
| `dataviz` | Before building ANY chart / KPI / dashboard | "use the dataviz skill" |
| `artifact-design` | Design a page as a polished mockup before Blade | "mock this up as an artifact first" |
| `/code-review` | Review your Blade + PHP diff | type `/code-review` |
| `/run` | Launch & screenshot the app to verify a change | type `/run` |

**Recommended flow:** ask Claude to *mock a page as an artifact → approve the look → port to Blade using the `x-ui.*` kit.*

---

## 2. MCP servers (free) — install commands

Run these in a terminal at the project root. On Windows, Claude Code reads `.mcp.json`.

```bash
# Context7 — up-to-date docs for Tailwind/Alpine/Bootstrap/Laravel injected on demand
claude mcp add context7 -- npx -y @upstash/context7-mcp

# Playwright — Claude drives a real browser to verify & screenshot your UI
claude mcp add playwright -- npx -y @playwright/mcp@latest

# shadcn — pull production component source on demand (React/Vue; use as islands)
claude mcp add shadcn -- npx -y shadcn@latest mcp

# Figma Dev Mode (free tier) — turn Figma frames into code.
# Requires the Figma desktop app running with Dev Mode MCP enabled, then:
claude mcp add --transport sse figma http://127.0.0.1:3845/sse
```

Verify with: `claude mcp list`. Remove one with: `claude mcp remove <name>`.

> Highest ROI for this repo: **Context7** (kills outdated-API guessing) + **Playwright** (visual verification of admin pages).

---

## 3. The Blade UI Kit

Global anonymous components in `resources/views/components/ui/`. Usable from **any module** with no registration.

Preview them live (local only): **http://localhost/Jambo/ui-kit**

### Components

**`<x-ui.stat-card>`** — KPI tile (replaces the hand-written cards in dashboards)
```blade
<x-ui.stat-card
    label="Revenue" value="UGX 4.28M"
    icon="ph ph-coins" accent="success"
    :trend="12.4" sub="vs last month" href="{{ route('...') }}" />
```
Props: `label, value, sub, icon, accent(primary|success|danger|warning|info|secondary), trend, trendSuffix, href`.

**`<x-ui.card>`** — panel with header + optional actions/footer slots
```blade
<x-ui.card title="Recent uploads" icon="ph ph-film-strip" :padded="false">
    <x-slot:actions><a href="#" class="btn btn-sm btn-outline-primary">View all</a></x-slot:actions>
    <table class="table mb-0">...</table>
    <x-slot:footer>Showing 10 of 240</x-slot:footer>
</x-ui.card>
```
Use `:padded="false"` when wrapping a flush table.

**`<x-ui.page-header>`** — title + subtitle + actions (replaces the repeated `d-flex justify-content-between` header)
```blade
<x-ui.page-header title="Movies" subtitle="342 titles">
    <x-slot:actions><a href="#" class="btn btn-primary">Add movie</a></x-slot:actions>
</x-ui.page-header>
```

**`<x-ui.badge>`** — status pill
```blade
<x-ui.badge variant="success" dot>Published</x-ui.badge>
<x-ui.badge variant="secondary" :soft="false">Archived</x-ui.badge>
```

**`<x-ui.empty-state>`** — friendly empty placeholder
```blade
<x-ui.empty-state icon="ph ph-film-strip" title="No episodes" message="Add the first one.">
    <x-slot:action><button class="btn btn-sm btn-primary">Add episode</button></x-slot:action>
</x-ui.empty-state>
```

**`<x-ui.chart>`** — ApexCharts wrapper (Alpine-driven, consistent theming)
```blade
<x-ui.chart type="area" :height="280"
    :series="[['name' => 'Views', 'data' => $daily]]"
    :options="['xaxis' => ['categories' => $labels]]" />
```
Types: `area|line|bar|donut|radialBar`. Requires ApexCharts + Alpine in the page (both already in your bundle; the demo page loads them from CDN).

### Requirements
- **Bootstrap 5** CSS (already used across admin panels).
- **Phosphor Icons** (`ph ph-*`) — the app-wide icon standard, already loaded in every layout.
- `color-mix()` for soft tints — supported in all evergreen browsers.

---

## Cleanup (after previewing)
- `resources/views/ui-kit-demo.blade.php` — demo page
- the `/ui-kit` route block in `routes/web.php`

The `resources/views/components/ui/` kit is meant to stay.
