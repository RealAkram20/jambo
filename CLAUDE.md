# Jambo

<!-- AIOS link. Added 2026-08-29. Keep this section at the top. -->

## This project is registered in the AIOS

Rio's operating system and long-term memory live at **`D:\OS`**. This project
is not an island: what is learned here is recorded there, and the standards
that govern the work are installed machine-wide.

**Its knowledge base is `D:\OS\jambo-os\`.** Read `D:\OS\jambo-os\index.md`
before answering anything about this project's status, client, commercials or
history. Do not re-derive from the code what the wiki already records.

**Before starting work, read:**

1. `D:\OS\jambo-os\index.md`, then `D:\OS\jambo-os\CLAUDE.md`
2. `~/.claude/skills/` — the standards that govern every project on this
   machine. `worklog` before your first edit, then
   `engineering-standards` / `wordpress` / `screen` / `ui-performance` as the
   work demands.
3. **`apple-design`, for anything in `mobile/`.** Rio's ruling, 2026-09-09:
   the Expo app is a cross-platform product and its motion, gesture and
   material behaviour follow that skill. It is written for the web, so
   translate rather than copy — springs are `Animated.spring` on the native
   driver, not Motion; there is no `backdrop-filter` in React Native, so a
   translucent layer is `expo-blur` or a gradient scrim, whichever the
   captured token actually says the website uses. The principles carry over
   unchanged: respond on press-down, track a drag 1:1, never lock out input
   during a transition, animate from the on-screen value rather than the
   target, and honour reduced motion. See the plan's §6.4.

**Before finishing, use `self-improve`:**

| What you learned | Where it goes |
|---|---|
| A rule for all projects | `~/.claude/skills/` |
| A rule for this project only | this file, or `AGENTS.md` |
| An architectural decision | `docs/adr/` in this repo |
| A business decision | `D:\OS\decisions\log.md` |
| A fact about the client, money or status | `D:\OS\jambo-os\` as a wiki page |
| Session state, gotchas, what you did NOT build | the worklog in this repo |

**The trigger is the second time.** First occurrence is an incident. Second is
a pattern, and a pattern belongs in a standard.

## Modular, on both sides

Rio, 2026-09-09:

> The system is modular and so should be the app, to make the work more easier
> more approachable when implementing or debugging existing features. We need
> to be more professional and future proof for expansion. Our vision is to make
> Jambo enterprise grade, unbreakable no matter the traffic we are running.

He extended it to every project the same evening, so it now lives in
`D:\OS\references\standard-stack.md` beside the stack itself and in
`D:\OS\decisions\log.md`. **Departing from it is an ADR.**

The backend already is this: thirteen self-contained Laravel modules. The
mobile app is not — one folder per file-type, and an `endpoints.ts` past six
hundred lines carrying catalogue, auth, profile, wallet, referrals,
preferences and playback together, which is why three concurrent sessions kept
colliding in one file over unrelated features.

**New work lands feature-shaped. Existing flat code is converted deliberately,
in its own commit, never as a side effect of a feature.** Restructuring
mid-slice is the failure mode, not the fix.

**"Unbreakable no matter the traffic" is checkable, not aspirational:** no
per-server state where there will be more than one server, no unbounded
queries, no synchronous work on a request path that could be queued, money
paths idempotent.

## One design for every overlay, and it is enforced

Rio, 2026-09-10, after a device sign-out opened an Android system dialog:

> we need a universal design for our system so that we don't use these default
> generic design and make sure all the other agents use it when it's needed it
> should be enforced

**`mobile/src/ui/overlay.tsx` is the only place `Modal` or `Alert` may be
imported from `react-native`.** Everything else uses what it exports:

| Need | Use | Never |
|---|---|---|
| Ask before something irreversible | `useConfirm()` | `Alert.alert` |
| A bottom sheet, picker or form panel | `Sheet` | a hand-rolled `Modal` |

**This is a lint error, not a convention**, because a convention is exactly
what produced the problem. `Alert.alert` drew a grey slab with teal
capitalised buttons in the middle of a blue product, and six screens had each
built their own sheet — the *tokens* were shared, so they still looked alike,
but the markup was not, so the Android back button, the safe-area padding and
the scrim's press target were solved five times and correctly a different
number of times.

`eslint.config.js` carries a **shrinking allowlist** of the six files that
predate the rule. Nothing new may be added to it. Delete a line when its file
moves to `Sheet`; when the list is empty, delete the block. Converting one is
deliberate work in its own commit — restructuring inside somebody else's
active feature is the failure mode, not the fix.

**The general rule this is an instance of:** when a platform default shows
through, that is a missing component, not a styling problem. Build it once in
`ui/`, express it in tokens, and make the lint refuse the primitive.

## Find the real problem before writing the fix

Rio, the same day: do not keep bringing new solutions over and over without
looking for the real problem and seeing what exists — he does not want a dirty
codebase.

In practice, and each of these has already cost time here: grep for an
existing helper before writing one (`app/helpers.php` has `media_url`,
`media_img`, `media_srcset`; `SettingController` has `normalizeMediaUrl`); one
mechanism per problem, so when a config change settles a question, delete the
helper that also settled it; and ask the other running sessions rather than
guessing at their half of a shared failure.

## Ground rules from `jambo-setup.md`, and they are binding

This codebase is the **Streamit** template by Iqonic Design. Never modify template CSS, JS or Blade layouts directly; never invent new UI components; customise only through the config points the template provides. A change that breaks those rules breaks the upgrade path.

`CHANGELOG.md` records why each change was made, not just what changed. Read it before assuming why something is the way it is, and keep the habit going.

## The code graph

This repo carries a **Graphify** code graph at `graphify-out/` — local
tree-sitter AST, no LLM tokens, nothing leaves the machine.

```bash
graphify query "how does X work"   # traversal, token-budgeted
graphify explain "ClassName"       # a node and its neighbours
graphify affected "ClassName"      # what breaks if this changes
graphify god-nodes --top 10        # architectural hubs
graphify update . --no-cluster     # refresh after code changes (free, fast)
```

**Precedence.** The graph answers *structure* only. For status, client,
commercials, history and decisions, `D:\OS\jambo-os\index.md` still comes first, and this
repo's own docs outrank anything inferred from code. Never let a graph query
replace reading the wiki.

If `GRAPH_REPORT.md` says the graph was built from an older commit, run
`graphify update . --no-cluster` before trusting it. Setup notes and the two
known traps are in `D:\OS\references\code-graph.md`.
