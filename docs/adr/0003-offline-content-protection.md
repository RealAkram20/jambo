# ADR-0003: Offline downloads live in an encrypted app-private cache under a server licence; Widevine is deferred on trigger

**Status:** Accepted
**Date:** 2026-09-07 (proposed) · 2026-09-08 (accepted by Rio, unamended)
**Plan:** [docs/plans/mobile-offline-app.md](../plans/mobile-offline-app.md) §3.2, §4.3

## Context

The requirement: viewers download movies and series and watch offline, but are
never given the file. Delivery today is a progressive MP4 on Backblaze B2
behind a token-signed Bunny pull zone; there is no HLS and no DRM, and the
on-server transcoder was removed in 1.4.0. The threats that matter, in order of
frequency here, are file sharing between phones (Xender, WhatsApp, USB),
extraction with adb or root, screen capture, and last and rarest, runtime
hooking on a rooted device.

## Decision

**Level 1 now.**

1. Bytes are downloaded by Media3 into a `SimpleCache` under the app's
   internal storage (`filesDir`), through an AES-CTR cipher sink/source whose
   16-byte secret is generated per install and stored wrapped by an Android
   Keystore key. On disk there is only ciphertext in a directory no user or
   other app can read without root.
2. `allowBackup=false`, backup extraction rules exclude the cache, and the
   player window sets `FLAG_SECURE`.
3. The player reads through the same cache chain; no plaintext file is ever
   written. Downloads are addressed by an app URI resolved to a fresh signed
   CDN URL at open time, so no signed URL is persisted.
4. Every download carries a **server-issued licence** (HMAC-signed): bound to
   user, device and subscription; `expires_at = min(issued + tier expiry
   days, subscription end + 3 days)`; a play window after first play; revocable
   at sync; refused offline on clock rollback.
5. **Not claimed:** resistance to a rooted, instrumented device. This is not
   DRM and must not be described as DRM.

**Level 2 later.** Widevine (hardware DRM) is added when a distributor or
partner contract requires it, when extraction of Jambo titles is evidenced, or
when the catalogue moves to HLS/DASH. It is additive: Media3's offline licence
helper on the same download engine, CENC packaging on the CDN, a licence vendor
that confirms persistent (offline) licences in writing.

## Alternatives considered

- **Widevine from day one.** Requires HLS/DASH packaging that does not exist,
  a licence vendor (monthly fee plus per-licence), and vendor confirmation of
  offline licences; delays offline by months for a threat class (rooted
  hooking) the ask does not name. Rejected for v1.
- **Plain files in app-private storage, no encryption.** Stops file sharing
  only; a copied directory plays anywhere. Rejected.
- **Proxying or re-encrypting video through the VPS.** Argued down already
  in the `StreamProxyController` docblock for a KVM 2. Rejected.

## Consequences

- Offline ships on the current MP4 pipeline with no new vendor and no
  per-licence cost.
- A precondition becomes hard: Bunny Token Authentication must be ON for the
  pull zone before downloads ship; unsigned URLs would be copyable by anyone
  who captured one.
- Offline viewing is credited to partners only through the bounded batch
  ingest in the plan (§3.4), off by default until observed.
