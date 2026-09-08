import { ApiClient } from './client';
import { JamboApi } from './endpoints';
import { getToken } from '../auth/currentToken';
import { emitUnauthenticated } from '../auth/sessionEvents';
import { API_BASE_URL } from '../config/env';
import { deviceId } from '../device/deviceId';

/**
 * One client, built once, for the life of the app.
 *
 * Its three moving parts are read through functions rather than passed as
 * values, so the client never holds a stale copy: the token changes on every
 * sign-in and sign-out, and the device id is null until the launch sequence
 * has resolved it.
 */
export const apiClient = new ApiClient({
  baseUrl: API_BASE_URL,
  getToken,
  getDeviceId: deviceId,
  onUnauthenticated: emitUnauthenticated,
});

export const api = new JamboApi(apiClient);
