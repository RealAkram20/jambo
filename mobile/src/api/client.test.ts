import { ApiClient } from './client';
import { ApiError, NetworkError } from './errors';

function jsonResponse(status: number, body: unknown, headers: Record<string, string> = {}) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json', ...headers },
  });
}

function clientWith(overrides: Partial<ConstructorParameters<typeof ApiClient>[0]> = {}) {
  return new ApiClient({
    baseUrl: 'https://example.test/api/v1',
    getToken: () => null,
    getDeviceId: () => 'device-uuid-1234',
    ...overrides,
  });
}

const fetchMock = jest.fn();

beforeEach(() => {
  fetchMock.mockReset();
  globalThis.fetch = fetchMock as unknown as typeof fetch;
});

function lastHeaders(): Record<string, string> {
  const [, init] = fetchMock.mock.calls[0] as [string, RequestInit];
  return init.headers as Record<string, string>;
}

describe('X-Device-Id', () => {
  it('is sent on every request when signed out', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { success: true, message: '', data: {} }));

    await clientWith().request('/app/config');

    expect(lastHeaders()['X-Device-Id']).toBe('device-uuid-1234');
    expect(lastHeaders().Authorization).toBeUndefined();
  });

  it('is sent alongside the bearer token when signed in', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { success: true, message: '', data: {} }));

    await clientWith({ getToken: () => 'token-abc' }).request('/me');

    expect(lastHeaders()['X-Device-Id']).toBe('device-uuid-1234');
    expect(lastHeaders().Authorization).toBe('Bearer token-abc');
  });

  /*
   * The rule this enforces is the one in docs/api/openapi.yaml: rate limits key
   * on the device header before the address. A request that quietly went out
   * without it would work perfectly on one handset and put every viewer behind
   * a carrier NAT into a single bucket — an outage that cannot be reproduced.
   */
  it('refuses to send a request at all when the device id has not resolved', async () => {
    await expect(clientWith({ getDeviceId: () => null }).request('/app/config')).rejects.toThrow(
      /device id/i,
    );

    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('cannot be overridden by a caller', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { success: true, message: '', data: {} }));

    await clientWith({ getToken: () => 'real-token' }).request('/me', {
      headers: {
        'X-Device-Id': 'someone-elses-device',
        Authorization: 'Bearer stolen',
      },
    });

    expect(lastHeaders()['X-Device-Id']).toBe('device-uuid-1234');
    expect(lastHeaders().Authorization).toBe('Bearer real-token');
  });
});

describe('refusals', () => {
  it('carries the machine-readable code rather than the message', async () => {
    fetchMock.mockResolvedValue(
      jsonResponse(403, {
        success: false,
        code: 'SUBSCRIPTION_REQUIRED',
        message: 'Wording that will change',
        errors: {},
      }),
    );

    await expect(clientWith().request('/playback/sessions')).rejects.toMatchObject({
      code: 'SUBSCRIPTION_REQUIRED',
      status: 403,
    });
  });

  /*
   * Laravel's throttle middleware answers before the app's envelope exists, so
   * this body is a framework string with no `code` in it. It still has to
   * arrive as something a screen can branch on.
   */
  it('synthesises a code when the server sends no envelope', async () => {
    fetchMock.mockResolvedValue(
      new Response('Too Many Attempts.', {
        status: 429,
        headers: { 'Retry-After': '47' },
      }),
    );

    const error = await clientWith()
      .request('/auth/login', { method: 'POST' })
      .catch((e: unknown) => e);

    expect(error).toBeInstanceOf(ApiError);
    expect((error as ApiError).code).toBe('RATE_LIMITED');
    expect((error as ApiError).retryAfterSeconds).toBe(47);
  });

  /*
   * Two-factor arrives as a 422 with the next step of a *successful* sign-in
   * sitting at the root of the error body. A client that keeps only code,
   * message and field errors throws away the challenge token and the viewer
   * cannot get in at all.
   */
  it('keeps the challenge token from a two-factor refusal', async () => {
    fetchMock.mockResolvedValue(
      jsonResponse(422, {
        success: false,
        code: 'TWO_FACTOR_REQUIRED',
        message: 'Two-factor required.',
        errors: {},
        challenge_token: 'challenge-xyz',
      }),
    );

    const error = (await clientWith()
      .request('/auth/login', { method: 'POST' })
      .catch((e: unknown) => e)) as ApiError;

    expect(error.code).toBe('TWO_FACTOR_REQUIRED');
    expect(error.detail('challenge_token')).toBe('challenge-xyz');
  });

  it('reports a dropped request as a network failure, not a refusal', async () => {
    fetchMock.mockRejectedValue(new TypeError('Network request failed'));

    const error = await clientWith()
      .request('/home')
      .catch((e: unknown) => e);

    expect(error).toBeInstanceOf(NetworkError);
    expect(error).not.toBeInstanceOf(ApiError);
  });

  it('tells the session layer when a token is rejected', async () => {
    const onUnauthenticated = jest.fn();
    fetchMock.mockResolvedValue(
      jsonResponse(401, {
        success: false,
        code: 'UNAUTHENTICATED',
        message: '',
        errors: {},
      }),
    );

    await clientWith({ getToken: () => 'dead-token', onUnauthenticated })
      .request('/me')
      .catch(() => undefined);

    expect(onUnauthenticated).toHaveBeenCalledTimes(1);
  });
});
