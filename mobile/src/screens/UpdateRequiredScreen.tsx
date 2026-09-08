import { StyleSheet, View } from 'react-native';

import { useAuth } from '../auth/AuthProvider';
import { appVersion } from '../config/version';
import { Body, Caption, Screen, Title } from '../ui/components';
import { spacing } from '../ui/theme';

/**
 * The update gate.
 *
 * Shown when `GET /app/config`'s `min_app_version` is above this build, and it
 * is a dead end on purpose: this version can no longer be trusted to talk to
 * this server correctly, so there is nothing safe for it to offer.
 *
 * There is deliberately **no update button**. The Play listing does not exist
 * yet and the direct APK's install page is not written, so any button here
 * would either lead nowhere or lead somewhere invented. It arrives in Phase 5
 * with the two things it needs to point at — and on the `play` variant it must
 * point at the Play listing rather than the website, because ADR-0004's
 * consumption-only build may not lead a viewer off-platform.
 */
export function UpdateRequiredScreen() {
  const { config } = useAuth();
  const current = appVersion();

  return (
    <Screen>
      <View style={styles.centre}>
        <Title>Update Jambo</Title>
        <Body>This version of the app is no longer supported. Please install the latest one.</Body>

        {config?.min_app_version !== undefined && current !== null ? (
          <View style={styles.detail}>
            <Caption>{`You have ${current}. Jambo needs ${config.min_app_version} or later.`}</Caption>
          </View>
        ) : null}
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  centre: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: spacing.md,
  },
  detail: { marginTop: spacing.lg },
});
