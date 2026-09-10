import { ApiError, NetworkError, type ValidationErrors } from './errors';

/**
 * The success envelope every endpoint returns.
 *
 * `docs/api/openapi.yaml`: every response is `{success, message, data}` or
 * `{success:false, code, message, errors}`. Nothing in this app reads a bare
 * body, so the unwrapping happens once, here.
 */
export type ApiEnvelope<TData, TMeta = unknown> = {
  success: true;
  message: string;
  data: TData;
  meta?: TMeta;
};

export type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  body?: unknown;
  query?: Record<string, string | number | boolean | undefined>;
  /**
   * Extra request headers, merged over the client's own.
   *
   * `Authorization` and `X-Device-Id` are applied *after* this merge and
   * cannot be overridden through it. Neither is a caller's business: one is
   * the session and the other is the install's identity, and a screen that
   * could replace either by passing a header is a screen that can impersonate
   * another device by accident.
   */
  headers?: Record<string, string>;
  timeoutMs?: number;
  signal?: AbortSignal;
};

export type ApiClientConfig = {
  baseUrl: string;
  getToken: () => string | null;
  /**
   * The install's device uuid. Resolved once at launch, before any request.
   *
   * Not optional and not lazily awaited: see the header rules below.
   */
  getDeviceId: () => string | null;
  /** Called on any 401 so the session layer can clear a token that is now dead. */
  onUnauthenticated?: () => void;
};

/**
 * 15 seconds.
 *
 * Long enough for a first request on a weak upcountry connection to complete,
 * short enough that somebody tapping Sign in is not left looking at a spinner
 * with no idea whether it is working. Playback will want its own budget when
 * it arrives; this is the catalogue and account default.
 */
const DEFAULT_TIMEOUT_MS = 15_000;

export class ApiClient {
  constructor(private readonly config: ApiClientConfig) {}

  async request<TData, TMeta = unknown>(
    path: string,
    options: RequestOptions = {},
  ): Promise<ApiEnvelope<TData, TMeta>> {
    const response = await this.send(path, options);

    if (response.status === 204) {
      return { success: true, message: '', data: undefined as TData };
    }

    const payload = await readJson(response);

    if (!response.ok) {
      throw toApiError(response, payload);
    }

    return payload as ApiEnvelope<TData, TMeta>;
  }

  private async send(path: string, options: RequestOptions): Promise<Response> {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), options.timeoutMs ?? DEFAULT_TIMEOUT_MS);

    if (options.signal) {
      options.signal.addEventListener('abort', () => controller.abort());
    }

    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...options.headers,
    };

    /*
     * X-Device-Id, on every request, signed in or not.
     *
     * `docs/api/openapi.yaml` states the rule and the reason: rate limits key
     * on this header before they fall back to the address. Without it, every
     * handset behind one carrier NAT — which in Uganda is most of them, on
     * every network — shares a single bucket, and the first busy evening looks
     * like an outage that cannot be reproduced anywhere.
     *
     * It throws rather than omitting the header. A missing device id is a bug
     * in the launch sequence, and the whole failure mode here is that it is
     * invisible until it is expensive: the app works perfectly on a desk, on
     * one device, on Wi-Fi. Failing loudly in development is the only moment
     * this gets caught cheaply.
     */
    const deviceId = this.config.getDeviceId();
    if (deviceId === null || deviceId === '') {
      throw new Error(
        'No device id. ensureDeviceId() must resolve before the first request — see src/device/deviceId.ts.',
      );
    }
    headers['X-Device-Id'] = deviceId;

    const token = this.config.getToken();
    if (token !== null) {
      headers.Authorization = `Bearer ${token}`;
    }

    /*
     * FormData goes through untouched, and NOT setting the header is the
     * whole trick.
     *
     * A multipart body is only parseable with the boundary string that
     * separates its parts, and that boundary is generated when the request is
     * built. Setting `Content-Type: multipart/form-data` by hand sends the
     * type without the boundary, and the server then sees a body it cannot
     * split — which surfaces as an empty `$request->file()` and a validation
     * error saying the file is required, on a request that plainly contained
     * one. Leaving the header off lets fetch write both together.
     *
     * `JSON.stringify` on a FormData would likewise produce `{}` and lose the
     * file entirely, which is the same bug wearing a different hat.
     */
    const isMultipart = options.body instanceof FormData;

    if (options.body !== undefined && !isMultipart) {
      headers['Content-Type'] = 'application/json';
    }

    const body =
      options.body === undefined
        ? null
        : isMultipart
          ? (options.body as FormData)
          : JSON.stringify(options.body);

    let response: Response;

    try {
      response = await fetch(this.config.baseUrl + path + buildQuery(options.query), {
        method: options.method ?? 'GET',
        headers,
        // Omitted rather than set to undefined: `exactOptionalPropertyTypes`
        // treats those as different, and a GET carrying an explicit body key
        // fails on some runtimes.
        ...(body === null ? {} : { body }),
        signal: controller.signal,
      });
    } catch (cause) {
      throw new NetworkError('The request did not reach the server.', cause);
    } finally {
      clearTimeout(timeout);
    }

    if (response.status === 401) {
      this.config.onUnauthenticated?.();
    }

    return response;
  }
}

function buildQuery(query: RequestOptions['query']): string {
  if (!query) return '';

  const pairs = Object.entries(query).filter(([, value]) => value !== undefined);
  if (pairs.length === 0) return '';

  return '?' + pairs.map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`).join('&');
}

async function readJson(response: Response): Promise<unknown> {
  try {
    return await response.json();
  } catch {
    return null;
  }
}

/**
 * Turns a refusal into an `ApiError` carrying its machine-readable `code`.
 *
 * The fallbacks matter more than the happy path. A 429 from the auth throttle
 * is rendered by Laravel's framework and carries no envelope at all; a proxy
 * 502 carries HTML. Both must still arrive as something a screen can branch
 * on, so an absent `code` is synthesised from the status rather than left
 * undefined — branching on `undefined` is message-text branching wearing a
 * different hat.
 */
function toApiError(response: Response, payload: unknown): ApiError {
  const envelope = (payload ?? {}) as {
    code?: unknown;
    message?: unknown;
    errors?: unknown;
  };
  const retryAfter = response.headers.get('Retry-After');

  return new ApiError({
    code: typeof envelope.code === 'string' ? envelope.code : codeForStatus(response.status),
    message:
      typeof envelope.message === 'string'
        ? envelope.message
        : 'Something went wrong. Please try again.',
    status: response.status,
    errors: (envelope.errors as ValidationErrors) ?? {},
    retryAfterSeconds: retryAfter === null ? null : Number.parseInt(retryAfter, 10) || null,
    payload,
  });
}

function codeForStatus(status: number): string {
  if (status === 401) return 'UNAUTHENTICATED';
  if (status === 403) return 'FORBIDDEN';
  if (status === 404) return 'NOT_FOUND';
  if (status === 422) return 'VALIDATION_FAILED';
  if (status === 429) return 'RATE_LIMITED';

  return 'SERVER_ERROR';
}
