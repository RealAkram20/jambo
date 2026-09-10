# ADR-0005: Over-the-air updates for the mobile app

**Status:** Proposed
**Date:** 2026-09-10
**Asked for by:** Rio — *"we don't want to keep on pushing updates for the
minor updates to playstore we want to be able to do our updates from our
side"*

## Context

Every change to the app today, however small, is a Play Store submission: a
new build, a review queue measured in hours to days, and a staged rollout. A
typo on the Membership screen costs the same process as a new feature. Rio
wants to ship those from his own side.

**The mechanism exists and is standard.** React Native ships its screens as
JavaScript, and `expo-updates` lets a running app fetch a new JavaScript
bundle and its assets from a URL, verify it, and swap it in at the next
launch. Expo's own hosted service (EAS Update) and self-hosting both speak the
same protocol.

**Google Play permits this**, and that is not a grey area: the Device and
Network Abuse policy allows code loaded through an interpreter where it does
not change the app's primary purpose. This is how most React Native apps in
the store already work.

**What it cannot do is the part that must be said out loud.** An over-the-air
update carries JavaScript and assets. It cannot carry:

- a new native library — a payment SDK, a video codec, a picker;
- a new Android permission;
- an Expo or React Native SDK upgrade;
- anything in `app.config.ts` that ends up in the manifest — the name, the
  icon, the package id, a deep-link scheme.

So it removes **most** store submissions, not all. The honest promise is "a
copy fix, a layout change, a new screen built from existing pieces, a bug fix,
a feature flag — same day, no review", and "a new dependency with native code
still needs a release".

**Two constraints that decide whether this works in practice.**

`runtimeVersion` is the contract between a binary and an update. An update
only applies to a binary declaring the same runtime version, and getting it
wrong means either updates that silently never arrive or a JavaScript bundle
running against native code that does not match it — which crashes on launch,
on a device already in somebody's hand. The policy has to be `appVersion` or
a hand-set string bumped deliberately whenever native changes, and it has to
be written down.

An update is also **not** a rollback mechanism unless one is built. Shipping a
broken bundle to every phone at once is the failure this buys, and the answer
is publishing to a channel that a fraction of installs read first.

## Decision

**Adopt `expo-updates`, self-hosted on Jambo's own server.**

- The Laravel app serves the update manifest and the bundle from
  `/api/v1/app/updates`, behind the same CDN as the media.
- `runtimeVersion` is set explicitly and bumped only when the native side
  changes. The value lives beside `min_app_version` in settings so the server
  can refuse to serve an update to a binary that cannot run it.
- Publishing is a script in the repo, not a person's laptop: build the bundle,
  upload, write the manifest row, and record who published what.
- The existing `min_app_version` gate stays and keeps its job — it is the
  answer for the changes an update cannot carry, and the two mechanisms are
  complementary rather than alternatives.

**Self-hosted rather than EAS Update**, which is the part that answers what
Rio actually asked. EAS Update is faster to stand up and is a subscription
with a per-seat and per-update tier, hosted by a third party. Jambo already
serves media from its own infrastructure and already has an update-manifest
concept for the webapp. "From our side" is the requirement, and paying a
monthly fee to a company in another country to hold the app's code is the
opposite of it.

**The direct APK gets a second capability for free.** A sideloaded app may
replace its own binary, so the APK from jambofilms.com can offer a full update
— native changes included — without the store at all. The Play build must
never do this.

## Consequences

- One more store release is needed before any of this pays off: `expo-updates`
  is a native module, so the binary that can receive updates has to be built
  and shipped once.
- The dev client on this machine must be rebuilt at the same time, which is
  disruptive while several sessions are working, and is the reason this is a
  slice of its own rather than something done tonight.
- A publish is a deploy. It needs the same care as one: a changelog entry, a
  staged rollout, and a way back.
- The server gains a route that serves signed, versioned bundles, and that
  route is now part of the app's trusted boot path. It gets the same treatment
  as an auth endpoint.

## Alternatives considered

- **EAS Update.** Standing start in an afternoon, and it is exactly the
  dependency Rio's instruction is pushing back on. Reasonable as a fallback if
  self-hosting stalls; the client code is identical either way, so the choice
  is reversible.
- **Keep shipping through Play.** What happens today. Rejected — it is the
  problem.
- **A web view wrapper.** Would make every change instant and would throw away
  the native player, offline downloads and the TV build. Rejected outright.
