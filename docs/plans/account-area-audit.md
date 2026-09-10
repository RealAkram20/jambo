# The account area: navigation, design drift, and too many screens

**Written 2026-09-10 by jambo-68, on Rio's instruction.** He asked three
things at once, and they turn out to be one thing seen from three sides:

> fix the flow of all the accounts related screens for proper navigation, like
> for the watchlist it can not navigate to profile from here. and also screens
> like billing are generic design, it does not match the UI standards we have
> set. and also I have a feeling we have lots of screens more than necessary or
> needed from the profile part. inspect and give me a plan or report on which
> elements on which page should be where according to modern and consumer
> behaviour.

Everything below is measured against the code as it stands this morning, not
recalled. File names and counts are real and can be checked.

---

## 0. The answer in one page

**The navigation bug was worse than reported and is now fixed.** Rio hit it on
Watchlist. It was in fact three of the four tabs: `AppHeader` — the bar with
the logo, search and the account icon — was rendered by `HomeScreen` alone.
Movies, Series and Watchlist had no route to search or to the account at all.
Fixed, verified on the device, and the reason it cannot be fixed "properly" in
one place is recorded in `TabNavigator`.

**The design drift is real and it is bigger than Billing.** Five account
screens have the hero-and-tiles language Rio's mockups introduced; **eight do
not**. Billing is simply the one he opened. This is not sloppiness — the eight
were built before the mockups existed — but the account area now reads as two
products.

**There are 14 account screens against the website's 8 sections.** Three of
them should not exist as screens, and two more are one screen wearing two
names. My recommendation takes it to **9**, and none of the removals loses a
feature.

The single highest-value change is not on that list, though: **the profile menu
is a wall of nine identical rows with no hierarchy**, and that is what makes
the area feel large regardless of how many screens are behind it.

---

## 1. What exists today

Fourteen screens, and where each one's design comes from.

| Screen | File | Lines | Design language |
|---|---|---|---|
| Profile menu | `screens/ProfileMenuScreen.tsx` | 500+ | mockup |
| Membership | `screens/PlansScreen.tsx` | 690 | mockup |
| Security | `screens/SecurityScreen.tsx` | 700+ | mockup |
| Devices | `features/devices/DevicesScreen.tsx` | — | mockup |
| Wallet | `features/wallet/WalletScreen.tsx` | — | mockup |
| Refer & Earn | `features/referrals/ReferralsScreen.tsx` | — | mockup |
| **Profile** | `screens/ProfileScreen.tsx` | 385 | pre-mockup |
| **Edit profile** | `screens/ProfileEditScreen.tsx` | — | pre-mockup |
| **Billing** | `features/billing/BillingScreen.tsx` | 188 | pre-mockup |
| **Invoice** | `features/billing/InvoiceScreen.tsx` | 258 | pre-mockup |
| **Change password** | `features/security/ChangePasswordScreen.tsx` | — | pre-mockup |
| **Two-factor setup** | `features/security/TwoFactorSetupScreen.tsx` | — | pre-mockup |
| **Notifications** | `features/notifications/NotificationsScreen.tsx` | — | pre-mockup |
| **Notification settings** | `features/notifications/NotificationSettingsScreen.tsx` | 193 | pre-mockup |

The website has eight: Watchlist, Profile, Security, Devices, Notifications,
Membership, Wallet, Refer & Earn. **Billing is not one of them** — on the site
it is reachable only from the payment-complete page, which is why the app had
to invent an entry point for it.

---

## 2. Navigation

### 2.1 The bug Rio found — fixed

`AppHeader` lived in `HomeScreen`. The consequence, in full:

| Tab | Search | Account | Way to a profile |
|---|---|---|---|
| Home | yes | yes | direct |
| Movies | **no** | **no** | back to Home first |
| Series | **no** | **no** | back to Home first |
| Watchlist | **no** | **no** | back to Home first |

Now every tab renders it. **It is rendered per screen rather than once on the
tab navigator, and that is a scar rather than a preference.** Putting it on the
navigator is the obvious fix — one place, and a fifth tab would inherit it. It
black-screened the app: `TabNavigator` is imported by the root navigator, and
pulling `AppHeader` (and through it `AuthProvider`) into that module closed an
import cycle, so the bundle died at load with `Cannot read property
'EventEmitter' of undefined`. The screens are leaves and can import it safely.
The note is in `TabNavigator` so the next person does not rediscover it.

### 2.2 What is still wrong

- **Every account screen is a dead end but for the back arrow.** Once inside
  Wallet, the only ways out are back or the Android gesture. The website keeps
  its sidebar visible on every hub page, so you can go from Wallet to Security
  in one click; the app makes that four taps.
- **The profile menu is a screen, not a drawer**, which was the right call, but
  it means the menu is *gone* while you are on a destination. `openedRow` in
  `ui/profileMenu.ts` exists purely to paper over this — it remembers where you
  last went because it cannot know where you are.

**Recommendation (one):** give every account screen a persistent way back to
the menu, by making the account icon in `AppHeader` present on account screens
too rather than only on tabs. It is one prop on the stack's `screenOptions` and
it turns the area from a tree into a hub, which is what the website already is.

---

## 3. The design drift

Billing is not badly built. It uses `ListCard` and `ListRow`, the shared badge
and real tokens — it passes every rule in `screen`. What it does not have is
the language the mockups introduced afterwards:

| | mockup screens | pre-mockup screens |
|---|---|---|
| A headline block | yes | no |
| Icons in tiles | yes | no |
| State as a badge on the row | yes | mixed |
| A summary before the list | yes | no |

**This is a drift, not a defect, and it should be closed deliberately.** Two
of the eight are nearly free because the components now exist:

1. **Billing** — add a summary line (what you have spent, when the next charge
   falls) and tile the row icons. The order rows are already right.
2. **Notifications and Notification settings** — tile the icons; the rows are
   already the site's captured ones.

The other five are forms (`ChangePassword`, `TwoFactorSetup`, `ProfileEdit`)
and a document (`Invoice`). **A form should not get a hero.** Forms are the one
place where the mockup language would actively hurt: a 200-pixel headline above
two fields pushes the fields under the keyboard. Leave them.

---

## 4. Too many screens — the specific cuts

### 4.1 Profile and Edit profile are one screen wearing two names

`ProfileScreen` shows Email, Phone, Country, Member since, and a button to
`ProfileEditScreen`, which edits First name, Last name, Username, Email, Phone,
Country. **The read-only screen shows a subset of the fields the editor already
displays.** A viewer taps Profile, reads four values, taps Edit, and sees the
same four in boxes.

**Recommendation:** delete `ProfileScreen`; the menu's identity block opens the
editor directly. Member since is the only fact not on the editor — it moves
there as a caption. Saves 385 lines and one tap on the most-used path.

### 4.2 Notification settings is three switches behind a gear

`NotificationSettingsScreen` is 193 lines for a Delivery section with In-app,
Email and Push. The website has these on the notifications page itself.

**Recommendation:** fold the three switches into the top of the Notifications
screen as a collapsed section. One screen, one subject, and the gear stops
being a place you have to know about.

### 4.3 Invoice is a screen that should be a sheet

`InvoiceScreen` is 258 lines showing one order: reference, date, amount,
status, line items. It is opened from exactly one place and leads nowhere.

**Recommendation:** make it a `Sheet` from `ui/overlay.tsx` — the component
jambo-49 has just made mandatory for overlays. A receipt is a thing you glance
at and dismiss, not a place you go.

### 4.4 Keep, despite the instinct

- **Change password** and **Two-factor setup** stay separate. Both are guarded
  flows with their own error states, and 2FA setup is four steps with recovery
  codes at the end. Folding either into Security would put a multi-step flow
  inside a list.
- **Devices** stays. It is the only screen that can end a session on a lost
  phone, and it now carries the usage meter.
- **Wallet** and **Refer & Earn** stay separate even though they share a
  balance. One is money you have, the other is how you earn it.

**Net: 14 screens becomes 9.**

---

## 5. Where each element belongs

Ordered by how often a viewer of a streaming app actually opens it, which is
not the order the website's sidebar uses.

| Rank | Destination | What lives there |
|---|---|---|
| 1 | **Membership** | Plan, price, renewal date, plan ladder, and — moved here — *the next charge*. This is the single most-opened account page in every streaming product, because it answers "what am I paying". |
| 2 | **Watchlist** | Already a tab. Correct. |
| 3 | **Profile** (the editor) | Identity, avatar, contact fields, Member since. |
| 4 | **Security** | Password, two-factor, email, phone, recent activity, close account. |
| 5 | **Devices** | Sessions, stream usage, sign-out. |
| 6 | **Notifications** | The inbox, with delivery switches at its head. |
| 7 | **Billing** | Order history, spend summary, invoices as sheets. |
| 8 | **Wallet** | Balance, ledger, withdrawal. |
| 9 | **Refer & Earn** | Code, link, share, earnings. |

**Two moves worth arguing for:**

- **The next charge belongs on Membership, not Billing.** "When am I next
  charged" is a membership question that people currently have to find in an
  order list. It is one line on a card they already open.
- **Recent activity stays on Security *and* Devices.** It looks like
  duplication and is not: on Security it is three rows answering "has anyone
  else been in my account", and on Devices it is the full list with the
  controls. The website makes the same split.

---

## 6. The thing that actually makes it feel big

**The profile menu is nine identical rows.** Watchlist, Profile, Security,
Devices, Notifications, Membership, Billing, Wallet, Refer & Earn — same height,
same weight, same chevron, no grouping. Nothing tells a viewer that Membership
matters more than Billing, so the eye reads all nine every time and the area
feels like a settings app.

**Recommendation:** group them, with the same rows and no new screens.

```
[ identity block ]
[ Membership card — plan, renews on, one tap ]

ACCOUNT      Profile · Security · Devices
VIEWING      Watchlist · Notifications
MONEY        Billing · Wallet · Refer & Earn

[ Sign out ]
```

Three labelled groups and one promoted card. It is the cheapest change in this
document and it is the one Rio would feel first.

---

## 7. Order of work

1. **Navigation** — done and verified.
2. **The menu grouping** (§6). Half a day, one file, no API.
3. **Profile absorbed into the editor** (§4.1). One file deleted, one route.
4. **Notification settings folded in** (§4.2), invoice becomes a sheet (§4.3).
5. **Billing summary and tiles** (§3). Needs one API addition: the next charge
   date, which `GET /subscription` already has as `ends_at`.

Nothing here needs a migration and nothing touches money paths.

---

## 8. What I am not recommending

- **Do not make the account a fifth tab.** The website's bottom bar has four
  destinations and the app ports it; a fifth would be a navigation the product
  does not have.
- **Do not merge Security and Devices.** They read as one subject and behave as
  two: one is settings, the other is a live list with destructive controls.
- **Do not give the forms heroes.** Named above, and it is the mistake this
  drift invites.

---

## 9. Addendum, 2026-09-10: the copy that was not true

Rio, after reading the above and opening the profile editor:

> the problem is that we have hardcoded texts in these profile pages with fake
> information like the password reset is done on the webapp, profile pic and
> all other parts

He is describing a category the audit above missed, and it is worse than
design drift: **the screens make claims about the product that are false.**
Every one of them was written when it was true and never revisited.

### 9.1 What was found, and what each one cost

| Screen | The claim | The truth |
|---|---|---|
| Profile editor | *"Profile pictures are changed on the Jambo website"* | **False.** `ProfileScreen`, one tap away, has had a working picker and upload for days. The app was sending people to a browser for something it already did. |
| Profile editor | *"Your password and two-factor settings are changed on the Jambo website"* | **False** since `ChangePassword` and `TwoFactorSetup` shipped. |
| Profile editor | *(nothing at all about the email)* | The address had no verified state on screen **and the server never sent the link it promised** — see §9.2. |
| Membership | *"Plans are managed on the Jambo website"* | True. ADR-0004. Leave. |
| Forgot password | *"…opens on the Jambo website"* | True. The reset link is a web page. Leave. |
| Invoice | *"Open this invoice on the Jambo website"* | True, but it is an instruction with no link attached. Give it one or cut it. |

All three false ones are fixed. **The lesson is the pattern, not the three
lines:** a sentence that says "this happens elsewhere" is a claim about
capability, and capability moves. Every one of these was correct on the day it
was written.

**Recommendation (one):** no screen may say where something is done unless it
also cannot do it. When a feature lands, the sentence that used to apologise
for its absence is part of the feature's diff.

### 9.2 The defect underneath the copy

`ProfileController::update` cleared `email_verified_at` when the address
changed and answered *"Check your new address for a verification link"* —
**and sent nothing.** The one moment a person is actively watching an inbox was
the moment no mail came.

The existing test passed throughout, because it asserted the flag and never
asked what clearing it was for. That is the fourth time in one night that an
assertion was true and useless, and it is now in `engineering-standards`.

### 9.3 Passwords

Rio, same message:

> we need to have the hide and unhide for all password fields including in the
> auth. and we need the strength helper as we did in Kangaru

**The reveal existed on two screens of eight.** Sign in and Register each had
their own hand-positioned toggle over their own field; the six fields behind
them — change password's three, two-factor's confirmation and Security's two
prompts — had none. Somebody typing a long password into an unreadable box on
a phone keyboard cannot find their own typo, and the only recovery is to clear
it and start again.

This is jambo-49's bottom-sheet finding one layer down: two implementations
that looked identical **because they shared a token and a child component**,
while the offset, the hit area and the accessibility state were solved
separately. There is one `PasswordField` now.

**The strength meter is Kangaru's, ported rather than rewritten**, on Rio's
instruction. The floor changed from 6 to 8 because that is what
`Password::defaults()` holds on this server, and the dictionary names Jambo.
Its nineteen tests came with it and pass unchanged — which is the argument for
porting over writing: the reasoning about passphrases, about naming obvious
junk rather than scoring it, and about a visible scale was already done and
already defended.

**It belongs under a NEW password box and nowhere else.** Under "Current
password" it grades something the viewer cannot change from that box; under
"Confirm" it grades the same string twice; on sign-in it grades a password that
already exists.

### 9.4 What this changes in the plan above

§4.1 said delete `ProfileScreen` and fold it into the editor. **That is now
more urgent, not less.** The avatar upload lived on the read-only screen while
the editor denied the capability existed — a split so bad that one half was
lying about the other. The upload has moved into a shared hook and both call
it, which is the first half of that merge done.

The ordering in §7 stands, with one addition at the top:

0. **Sweep every screen for sentences that point at the website**, and delete
   the ones the app has outgrown. It is an afternoon and it is the change that
   most affects whether the app feels finished.
