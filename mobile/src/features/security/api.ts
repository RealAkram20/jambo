import { apiClient } from '../../api/jambo';

/**
 * Password and two-factor calls.
 *
 * A feature module rather than more methods on `JamboApi`, per Rio's modular
 * ruling of 2026-09-09: `src/api/endpoints.ts` is past seven hundred lines and
 * is where three concurrent sessions kept colliding over unrelated features.
 *
 * Reading security state stays on `api.security()`, because `SecurityScreen`
 * already calls it and a second function returning the same payload on a
 * different key is exactly the drift the query-key rule exists to prevent.
 * What lives here is everything that CHANGES something.
 */

/** A fresh batch of recovery codes, and nothing else. */
export type RecoveryCodes = { recovery_codes: string[] };

/**
 * Change the password.
 *
 * `PUT`, not `POST` — worth stating because `docs/api/coverage.md` and this
 * slice's brief both name it as a POST, and the router says otherwise.
 *
 * **Every other device is signed out**, which is the server's decision and not
 * this client's: `PasswordController::change` revokes every device and every
 * token except the one that made the call. The screen has to say so before the
 * viewer presses the button, because a password change that silently ends a
 * session on a television is a support ticket.
 *
 * `password_confirmation` is sent because the rule is `confirmed`; without it
 * the server refuses with a validation error on `password` rather than on the
 * field the viewer actually mistyped.
 */
export async function changePassword(input: {
  currentPassword: string;
  password: string;
  passwordConfirmation: string;
}): Promise<void> {
  await apiClient.request<null>('/auth/password', {
    method: 'PUT',
    body: {
      current_password: input.currentPassword,
      password: input.password,
      password_confirmation: input.passwordConfirmation,
    },
  });
}

/**
 * Start two-factor setup.
 *
 * Mints a pending secret AND the recovery codes, and returns both. **Two-factor
 * is not on after this call** — it is on only once `confirmTwoFactor`
 * succeeds. That is the server's design and the reason this is a flow rather
 * than a switch: a one-shot enable lets somebody lock themselves out with a
 * mis-scanned code.
 *
 * Called again while a setup is already pending, it mints a NEW secret and
 * discards the old one, so a screen must not call it to re-read a secret it
 * could have kept. `GET /account/security` returns the pending secret for
 * exactly that purpose.
 */
export async function startTwoFactor(): Promise<{ secret: string; recoveryCodes: string[] }> {
  const { data } = await apiClient.request<{ secret?: string; recovery_codes?: string[] }>(
    '/account/2fa',
    { method: 'POST' },
  );

  return { secret: data.secret ?? '', recoveryCodes: data.recovery_codes ?? [] };
}

/**
 * Finish setup by proving the authenticator holds the secret.
 *
 * A wrong code comes back as a 422 with code `TWO_FACTOR_INVALID`, which the
 * caller catches as an `ApiError` and reads by code, never by message.
 */
export async function confirmTwoFactor(code: string): Promise<string[]> {
  const { data } = await apiClient.request<RecoveryCodes>('/account/2fa/confirm', {
    method: 'POST',
    body: { code },
  });

  return data.recovery_codes ?? [];
}

/**
 * Turn two-factor off, or cancel a setup that has not been confirmed.
 *
 * **Costs the password, and that is a difference from the website.** The
 * website puts this behind password-confirmation middleware, which is a
 * session concept with no token equivalent, so the API asks for the password
 * on the call itself — including for the "Cancel setup" case, where the
 * website asks for nothing. Removing a second factor must cost more than
 * holding an unlocked handset.
 */
export async function disableTwoFactor(password: string): Promise<void> {
  await apiClient.request<null>('/account/2fa', {
    method: 'DELETE',
    body: { password },
  });
}

/**
 * A fresh batch of recovery codes. The previous batch stops working the
 * instant this returns, which is why the screen confirms first.
 */
export async function regenerateRecoveryCodes(): Promise<string[]> {
  const { data } = await apiClient.request<RecoveryCodes>('/account/2fa/recovery-codes', {
    method: 'POST',
  });

  return data.recovery_codes ?? [];
}

/**
 * The key `SecurityScreen` already writes.
 *
 * Named here so every mutation in this module invalidates the same string
 * rather than a hand-typed copy of it. Two places on one key must have one
 * shape; the cheapest way to keep that true is for there to be one spelling.
 */
export const securityKey = ['security'] as const;

/**
 * Close this account.
 *
 * `DELETE /account`, which costs the password and an explicit confirmation and
 * then signs every device out. Rio, 2026-09-10, asked for the button; the same
 * message told us it has to be one he can withdraw, so the caller checks
 * `features.account_deletion` before drawing it **and the server checks the
 * same setting before honouring this**. The client flag hides a row; the
 * server flag is the one that decides.
 *
 * **What it does is close, not erase.** The account is marked deactivated,
 * every device is revoked and every token is deleted, so sign-in stops working
 * everywhere immediately. Records are retained. The screen says so in as many
 * words, because a button labelled Delete that quietly keeps everything is the
 * kind of promise that ends up in a complaint.
 */
export async function deleteAccount(password: string): Promise<void> {
  await apiClient.request('/account', {
    method: 'DELETE',
    body: { password, confirm: true },
  });
}
