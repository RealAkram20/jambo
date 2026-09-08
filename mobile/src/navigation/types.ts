import type { NativeStackScreenProps } from '@react-navigation/native-stack';

/**
 * The signed-out stack.
 *
 * `TwoFactor` carries the challenge token as a route parameter rather than
 * holding it in a provider. It is a short-lived, single-use credential that
 * expires in five minutes, and a route parameter dies with the screen — which
 * is the lifetime it should have.
 */
export type AuthStackParams = {
  SignIn: undefined;
  TwoFactor: { challengeToken: string; email: string };
  ForgotPassword: undefined;
  Register: undefined;
};

export type AppStackParams = {
  Account: undefined;
  Devices: undefined;
};

export type AuthScreenProps<T extends keyof AuthStackParams> = NativeStackScreenProps<
  AuthStackParams,
  T
>;

export type AppScreenProps<T extends keyof AppStackParams> = NativeStackScreenProps<
  AppStackParams,
  T
>;
