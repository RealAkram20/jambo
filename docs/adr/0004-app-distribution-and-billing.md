# ADR-0004: Consumption-only on Google Play; in-app PesaPal on the direct APK

**Status:** Accepted
**Date:** 2026-09-07 (proposed) · 2026-09-08 (accepted by Rio, unamended)
**Plan:** [docs/plans/mobile-offline-app.md](../plans/mobile-offline-app.md) §3.3

## Context

Google Play's Payments policy (read 2026-09-07) requires Google Play Billing
for subscription content, video named explicitly, everywhere except India,
South Korea, the EEA and the US, and forbids leading users to another payment
method elsewhere. A consumption-only app, where nothing can be bought in the
app, is explicitly permitted. Uganda has no exception, and whether the
developer account can register as a Play merchant at all is unverified.
Jambo's viewers pay by mobile money through PesaPal; that flow already exists
on the website and in Kugawana's app.

## Decision

One app, two build variants selected by an environment flag at build time:

- **`play`**: the Google Play listing for phone, tablet and TV. Consumption
  only: no plan purchase, no checkout, no link to a payment page. Shows the
  active plan and renewal date; a neutral sentence that subscriptions are
  managed on the account's website, with no URL and no call to action.
- **`direct`**: the APK offered for download on the Jambo website (EAS
  `preview`-style build, as Kugawana ships). Includes Subscribe: PesaPal hosted
  checkout in a Custom Tab, deep-link return, status polling. TV builds never
  include it.

Google Play Billing is not integrated in v1.

## Alternatives considered

- **Play Billing.** 15–30 % fee, merchant eligibility unknown, and a second
  source of truth for subscriptions to reconcile with `user_subscriptions`.
  Deferred.
- **Play build with a link to the website checkout.** Violates the policy
  outside the named regions; a removal risk. Rejected.
- **Direct APK only.** Loses the TV catalogue and Play discovery. Rejected.

## Consequences

- Play review copy must be checked against the policy wording before each
  release.
- The website's install page must explain "Unknown sources" for the direct
  APK and keep the APK current (`GET app/config` min-version gate).
- Revisit if Google extends alternative billing to Uganda or if Play becomes
  the majority channel.
