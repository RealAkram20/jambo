# ADR-0002: The mobile and TV app is one Expo codebase with one Kotlin media module

**Status:** Accepted
**Date:** 2026-09-07 (proposed) · 2026-09-08 (accepted by Rio, unamended)
**Plan:** [docs/plans/mobile-offline-app.md](../plans/mobile-offline-app.md) §3.1

## Context

Jambo has no mobile client. The requirement is one app for Android phones,
tablets and Android TV that keeps the Streamit design and adds protected
offline downloads. The estate's standard stack is Laravel + React +
Expo/React Native (`D:\OS\references\standard-stack.md`), with two shipped
Expo apps to copy from (Kugawana, Kangaru). The one genuinely native problem,
downloading video into storage no other app or user can read and playing it
from there, has to be Android code whatever the app is written in.

## Decision

1. The app is an **Expo** project in `mobile/` in this repository, on the
   current SDK, using **`react-native-tvos`** with
   `@react-native-tvos/config-tv` so one codebase builds for phone/tablet and
   for Android TV (`EXPO_TV=1` in the TV EAS profile).
2. **One custom Expo Module in Kotlin, `expo-jambo-media`**, owns playback and
   downloads on Android Media3: the player view, the download service and
   manager, the encrypted cache and the licence vault. It is the only native
   code in the app and the only place content protection is enforced.
3. **Android only in v1.** The module keeps an iOS slot; nothing else assumes
   Android.
4. Auth, API client, query persistence, push and EAS release profiles are
   copied from Kangaru and Kugawana, not rewritten.

## Alternatives considered

- **Native Kotlin (Compose + Compose for TV).** Off the standard stack, two
  UI targets to maintain, no over-the-air updates, no reuse of the estate's
  app code. Rejected.
- **Iqonic's Streamit Flutter app and TV add-on.** Off-stack; built for
  Iqonic's API contract and their app design, not Jambo's website design or
  data model (VJs, splits, tiers); its offline feature is not the protection
  specified. Rejected.
- **Extend the PWA.** No Play/TV presence, no protected storage. Rejected.
- **react-native-video + The Widlarz Group Offline Video SDK.** Kept as the
  time-boxed fallback if the Phase 0 spike cannot produce the encrypted-cache
  path in three days. Not first choice because v1 does not need the Widevine
  it sells and per-impression pricing does not suit a fixed-cost operation.

## Consequences

- The design is reproduced from exported tokens, not shared components; the
  website stays Blade. This settles the "shared UI package" question in
  `standard-stack.md` for Jambo: **two token consumers, one token file**.
- `expo-jambo-media` becomes a reusable estate asset (any project that plays
  private media offline). Record it in
  `D:\OS\references\reusable-features.md` when it ships.
- The VPS deploy is unaffected: `mobile/` is inert to `git pull`.
