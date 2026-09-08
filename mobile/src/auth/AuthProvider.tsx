import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { api } from '../api/jambo';
import { ApiError } from '../api/errors';
import { sessionCredentials, type AppConfig, type Session } from '../api/endpoints';
import { ensureDeviceId } from '../device/deviceId';
import { appVersion, isUpdateRequired } from '../config/version';
import { setToken } from './currentToken';
import { onUnauthenticated } from './sessionEvents';
import { clearSession, readSession, writeSession } from './tokenStore';
import { filesFrom, readManifest, syncBranding, type BrandingFiles } from '../ui/brandingStore';

type SessionUser = NonNullable<Session['user']>;

export type AuthPhase = 'starting' | 'signedOut' | 'signedIn';

/**
 * What sign-in returns to the screen that called it.
 *
 * Two-factor is a *step*, not a failure, even though the server delivers it
 * inside a 422. Modelling it as a returned outcome rather than a thrown error
 * keeps the screen's logic honest: `two-factor` is a normal path with its own
 * next screen, and anything thrown really is something that went wrong.
 */
export type SignInOutcome = { kind: 'signedIn' } | { kind: 'two-factor'; challengeToken: string };

type AuthValue = {
  phase: AuthPhase;
  user: SessionUser | null;
  config: AppConfig | null;
  /** True when the server's `min_app_version` is above this build. */
  updateRequired: boolean;
  /** Set when the launch could not reach the server. The app still runs. */
  launchOffline: boolean;
  /**
   * The admin's branding as files on this device.
   *
   * Read from the manifest before the first network call, so a launch with no
   * connection is still branded, then refreshed if the server is advertising
   * something newer.
   */
  branding: BrandingFiles;
  signIn: (
    email: string,
    password: string,
    options?: { remember?: boolean },
  ) => Promise<SignInOutcome>;
  completeTwoFactor: (
    challengeToken: string,
    answer: { code: string } | { recovery_code: string },
  ) => Promise<void>;
  register: (input: {
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) => Promise<void>;
  signOut: () => Promise<void>;
  refreshConfig: () => Promise<void>;
};

const AuthContext = createContext<AuthValue | null>(null);

export function useAuth(): AuthValue {
  const value = useContext(AuthContext);
  if (value === null) throw new Error('useAuth must be used inside <AuthProvider>.');
  return value;
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [phase, setPhase] = useState<AuthPhase>('starting');
  const [user, setUser] = useState<SessionUser | null>(null);
  const [config, setConfig] = useState<AppConfig | null>(null);
  const [launchOffline, setLaunchOffline] = useState(false);
  const [branding, setBranding] = useState<BrandingFiles>({});

  /**
   * Take a session the server just issued.
   *
   * `remember` decides whether it is written to the keystore or held only in
   * memory for this run — which is what makes the sign-in screen's checkbox a
   * real control rather than decoration. The app's token has no timer expiry
   * (`docs/api/openapi.yaml` says so), so without this the session would
   * always outlive the app and "Remember me" would mean nothing. Unchecked, a
   * viewer on a shared handset is signed out the moment the app is killed.
   */
  const adopt = useCallback(async (session: Session, remember = true) => {
    const { token, user: signedIn } = sessionCredentials(session);
    if (remember) {
      await writeSession(token, signedIn);
    }
    setToken(token);
    setUser(signedIn);
    setPhase('signedIn');
  }, []);

  const forget = useCallback(async () => {
    await clearSession();
    setToken(null);
    setUser(null);
    setPhase('signedOut');
  }, []);

  /*
   * The launch sequence, and the order is load-bearing.
   *
   * `ensureDeviceId()` comes first because `ApiClient` throws rather than send
   * a request without `X-Device-Id` — every call below it depends on it having
   * resolved. The stored session is adopted before `GET /app/config` so a
   * returning viewer lands on their own screens rather than watching a sign-in
   * form appear and then vanish.
   */
  useEffect(() => {
    let cancelled = false;

    const boot = async () => {
      await ensureDeviceId();

      // Before anything touches the network: whatever branding this device
      // already holds. An offline launch is branded from this line alone.
      const heldBranding = await readManifest();
      if (!cancelled) setBranding(filesFrom(heldBranding));

      const stored = await readSession();
      if (cancelled) return;

      if (stored !== null) {
        setToken(stored.token);
        setUser(stored.user);
        setPhase('signedIn');
      } else {
        setPhase('signedOut');
      }

      /*
       * The config call must not be able to hold up the launch.
       *
       * It is the first network request the app makes, so it is the one most
       * likely to meet a dead connection — and a viewer whose subscription is
       * paid should reach their downloads and their account whether or not
       * this answered. A failure here sets the offline flag and nothing else.
       */
      try {
        const answered = await api.appConfig();
        if (!cancelled) {
          setConfig(answered);
          setLaunchOffline(false);
        }

        // Downloads only what actually changed, keeps what it already has when
        // the network fails, and never throws.
        const refreshed = await syncBranding(answered);
        if (!cancelled) setBranding(filesFrom(refreshed));
      } catch {
        if (!cancelled) setLaunchOffline(true);
      }
    };

    void boot();

    return () => {
      cancelled = true;
    };
  }, []);

  /*
   * A 401 from anywhere means this token is gone — most often because the
   * viewer booted this phone from the website's device list. Clearing the
   * session sends them to the sign-in screen instead of leaving them on a
   * signed-in shell where every screen fails.
   */
  useEffect(
    () =>
      onUnauthenticated(() => {
        void forget();
      }),
    [forget],
  );

  const signIn = useCallback(
    async (
      email: string,
      password: string,
      options?: { remember?: boolean },
    ): Promise<SignInOutcome> => {
      try {
        const session = await api.login(email, password);
        await adopt(session, options?.remember ?? true);
        return { kind: 'signedIn' };
      } catch (error) {
        if (error instanceof ApiError && error.code === 'TWO_FACTOR_REQUIRED') {
          const challengeToken = error.detail('challenge_token');
          if (challengeToken !== null) {
            return { kind: 'two-factor', challengeToken };
          }
        }
        throw error;
      }
    },
    [adopt],
  );

  const register = useCallback(
    async (input: {
      first_name: string;
      last_name: string;
      username: string;
      email: string;
      password: string;
      password_confirmation: string;
    }) => {
      // Signed in immediately, as the website's sign-up form does. Always
      // remembered: somebody who has just created an account has not been
      // offered the "remember me" choice, and asking them to sign in again on
      // the next launch would be a strange first experience.
      await adopt(await api.register(input), true);
    },
    [adopt],
  );

  const completeTwoFactor = useCallback(
    async (challengeToken: string, answer: { code: string } | { recovery_code: string }) => {
      await adopt(await api.twoFactorChallenge(challengeToken, answer));
    },
    [adopt],
  );

  /*
   * Local first, server second, and it succeeds either way.
   *
   * Somebody tapping Sign out on a phone with no signal is signed out of that
   * phone. The server call revokes the token properly when it can; when it
   * cannot, the token stays alive server-side until the viewer boots it from
   * the device list — which is exactly what that list is for, and a far better
   * outcome than a Sign out button that does nothing on a bad connection.
   */
  const signOut = useCallback(async () => {
    try {
      await api.logout();
    } catch {
      // Deliberately swallowed. See above.
    }
    await forget();
  }, [forget]);

  const refreshConfig = useCallback(async () => {
    try {
      setConfig(await api.appConfig());
      setLaunchOffline(false);
    } catch {
      setLaunchOffline(true);
    }
  }, []);

  const value = useMemo<AuthValue>(
    () => ({
      phase,
      user,
      config,
      updateRequired: isUpdateRequired(appVersion(), config?.min_app_version),
      launchOffline,
      branding,
      signIn,
      register,
      completeTwoFactor,
      signOut,
      refreshConfig,
    }),
    [
      phase,
      user,
      config,
      launchOffline,
      branding,
      signIn,
      register,
      completeTwoFactor,
      signOut,
      refreshConfig,
    ],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
