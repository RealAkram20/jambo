import type { components } from './schema';

/**
 * The codes the server can return, taken from the spec rather than retyped.
 *
 * `docs/api/openapi.yaml` states the rule this type enforces: *clients branch
 * on `code`, never on `message`.* Message text gets reworded — it is copy —
 * and a screen that switches on it breaks silently on a day nobody touched the
 * app. Because this type is generated, a code removed from the spec becomes a
 * compile error here rather than a branch that can no longer be reached.
 */
export type ApiErrorCode = NonNullable<components['schemas']['EnvelopeError']['code']>;

export type ValidationErrors = Record<string, string[]>;

export class ApiError extends Error {
  readonly code: ApiErrorCode | string;
  readonly status: number;
  readonly errors: ValidationErrors;
  readonly retryAfterSeconds: number | null;
  /**
   * The raw refusal body.
   *
   * Kept because a refusal is not always only a refusal. The two-factor path
   * is the case that forced it: `POST /auth/login` answers 422
   * `TWO_FACTOR_REQUIRED` with a `challenge_token` sitting at the root of the
   * error envelope, beside `code`. That token is the next step of a successful
   * sign-in arriving inside an error, and a client that keeps only code,
   * message and field errors throws away the one thing the screen needs next.
   */
  readonly payload: unknown;

  constructor(init: {
    code: ApiErrorCode | string;
    message: string;
    status: number;
    errors?: ValidationErrors;
    retryAfterSeconds?: number | null;
    payload?: unknown;
  }) {
    super(init.message);
    this.name = 'ApiError';
    this.code = init.code;
    this.status = init.status;
    this.errors = init.errors ?? {};
    this.retryAfterSeconds = init.retryAfterSeconds ?? null;
    this.payload = init.payload ?? null;
  }

  /** A string field from the refusal body, when the server sent one. */
  detail(field: string): string | null {
    if (typeof this.payload !== 'object' || this.payload === null) return null;
    const value = (this.payload as Record<string, unknown>)[field];
    return typeof value === 'string' && value !== '' ? value : null;
  }

  /** The first message for a field, for rendering under the input it belongs to. */
  fieldError(field: string): string | null {
    return this.errors[field]?.[0] ?? null;
  }
}

/**
 * The request never reached a server, or never came back.
 *
 * Kept distinct from `ApiError` because the two need opposite treatment on a
 * Ugandan mobile connection: a refusal is an answer and should be shown, while
 * a dropped request is not an answer at all and is worth retrying. Collapsing
 * them is how an app ends up telling somebody their password is wrong because
 * a tower handed off mid-request.
 */
export class NetworkError extends Error {
  constructor(
    message: string,
    readonly cause?: unknown,
  ) {
    super(message);
    this.name = 'NetworkError';
  }
}

/**
 * A field the app cannot proceed without was absent from a response.
 *
 * This exists because of a real property of the contract: `docs/api/openapi.yaml`
 * declares `required` on its envelopes but not on most payload schemas, so
 * every generated payload field arrives as `T | undefined`. Rather than pepper
 * the screens with non-null assertions — which turn a missing field into an
 * unattributable crash three components away — the two or three fields that
 * are genuinely load-bearing are checked at the boundary, and name themselves
 * when they are missing.
 */
export class ContractError extends Error {
  constructor(field: string) {
    super(`The server did not send ${field}.`);
    this.name = 'ContractError';
  }
}

export function required<T>(value: T | null | undefined, field: string): T {
  if (value === null || value === undefined) throw new ContractError(field);
  return value;
}

/** True when this is worth offering a Retry button for. */
export function isRetryable(error: unknown): boolean {
  if (error instanceof NetworkError) return true;
  if (error instanceof ApiError) return error.status >= 500 || error.status === 429;
  return false;
}
