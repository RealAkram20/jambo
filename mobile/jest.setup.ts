/**
 * Native modules this app calls have no implementation under Jest. They are
 * mocked here rather than per-test so a screen test that renders a provider
 * does not have to know which native module the provider reaches for.
 *
 * The suites that carry the real weight — the API client's header rules and
 * the device identity — touch almost none of this. They are pure TypeScript
 * over injected ports, which is why they are the ones worth trusting.
 */

/**
 * An in-memory keystore rather than a set of `jest.fn()` stubs that always
 * answer null.
 *
 * The distinction matters for one test in particular: the device uuid must
 * survive a sign-out. A mock that never returns what was written cannot tell
 * the difference between "the uuid persisted" and "a fresh uuid was minted on
 * every read", which is the bug the test exists to catch.
 */
// The `mock` prefix is required: jest hoists `jest.mock()` factories above
// every other statement, and only names beginning with `mock` are allowed to
// be referenced from inside one.
const mockSecureStore = new Map<string, string>();

jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(async (key: string) => mockSecureStore.get(key) ?? null),
  setItemAsync: jest.fn(async (key: string, value: string) => {
    mockSecureStore.set(key, value);
  }),
  deleteItemAsync: jest.fn(async (key: string) => {
    mockSecureStore.delete(key);
  }),
}));

/*
 * A *different* uuid on every call, deliberately.
 *
 * A constant here looks harmless and quietly disarms the most important test
 * in the suite: if `clearSession()` started wiping the device id, the next
 * `ensureDeviceId()` would mint a replacement — and with a fixed mock the
 * replacement is indistinguishable from the original, so "the device id
 * survives a sign-out" passes while the thing it names is broken. Found by
 * mutating `clearSession()` and watching that test stay green.
 */
let mockUuidCounter = 0;

jest.mock('expo-crypto', () => ({
  randomUUID: jest.fn(() => `00000000-0000-4000-8000-${String(++mockUuidCounter).padStart(12, '0')}`),
}));

jest.mock('expo-device', () => ({
  isDevice: false,
  modelName: 'Test Handset',
  deviceType: 1,
}));

jest.mock('expo-constants', () => ({
  __esModule: true,
  default: { expoConfig: { version: '0.1.0' } },
}));

beforeEach(() => {
  mockSecureStore.clear();
});
