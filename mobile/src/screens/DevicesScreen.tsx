import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Alert as NativeAlert, FlatList, StyleSheet, Text, View } from 'react-native';

import { api } from '../api/jambo';
import { ApiError, NetworkError } from '../api/errors';
import type { Device } from '../api/endpoints';
import { deviceId } from '../device/deviceId';
import { useAuth } from '../auth/AuthProvider';
import {
  Alert,
  Button,
  Caption,
  Divider,
  EmptyState,
  ErrorState,
  Loading,
  Screen,
} from '../ui/components';
import { preloaderSource } from '../ui/branding';
import { colors, spacing, typography } from '../ui/theme';

function describe(device: Device): string {
  if (device.kind === 'browser') {
    return device.name ?? 'A web browser';
  }

  const parts = [device.model, device.app_version !== undefined ? `v${device.app_version}` : null]
    .filter((part): part is string => typeof part === 'string' && part !== '')
    .join(' · ');

  return parts !== '' ? parts : 'App install';
}

export function DevicesScreen() {
  const queryClient = useQueryClient();
  const { branding } = useAuth();
  const [banner, setBanner] = useState<string | null>(null);

  const devices = useQuery({
    queryKey: ['devices'],
    queryFn: () => api.devices(),
  });

  const revoke = useMutation({
    mutationFn: (id: string) => api.revokeDevice(id),
    onSuccess: async () => {
      setBanner(null);
      await queryClient.invalidateQueries({ queryKey: ['devices'] });
    },
    onError: (error: unknown) => {
      if (error instanceof NetworkError) {
        setBanner('Could not reach Jambo. The device was not signed out.');
      } else if (error instanceof ApiError && error.code === 'DEVICE_NOT_FOUND') {
        // Already gone — signed out from somewhere else in the meantime. The
        // list is simply refreshed rather than an error shown for something
        // that has already happened.
        void queryClient.invalidateQueries({ queryKey: ['devices'] });
      } else {
        setBanner(error instanceof ApiError ? error.message : 'The device was not signed out.');
      }
    },
  });

  const confirmRevoke = (device: Device, id: string) => {
    NativeAlert.alert('Sign this device out?', `${describe(device)} will have to sign in again.`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Sign out',
        style: 'destructive',
        onPress: () => revoke.mutate(id),
      },
    ]);
  };

  if (devices.isPending) {
    return (
      <Screen>
        <Loading label="Loading your devices" source={preloaderSource(branding)} />
      </Screen>
    );
  }

  if (devices.isError) {
    return (
      <Screen>
        <ErrorState
          message={
            devices.error instanceof NetworkError
              ? 'Could not reach Jambo. Check your connection.'
              : 'Your devices could not be loaded.'
          }
          onRetry={() => void devices.refetch()}
        />
      </Screen>
    );
  }

  const thisDevice = deviceId();

  return (
    <Screen>
      <FlatList
        data={devices.data.devices}
        keyExtractor={(device, index) => device.id ?? String(index)}
        ItemSeparatorComponent={Divider}
        refreshing={devices.isFetching}
        onRefresh={() => void devices.refetch()}
        ListHeaderComponent={
          <View style={styles.header}>
            {banner !== null ? <Alert tone="error">{banner}</Alert> : null}
            <Caption>
              {devices.data.countsAppDevices
                ? 'Everything signed in to your account. App installs count towards how many devices can watch at once.'
                : 'Everything signed in to your account. Only browsers count towards how many can watch at once.'}
            </Caption>
          </View>
        }
        ListEmptyComponent={
          <EmptyState
            title="Nothing else is signed in"
            detail="Devices you sign in on will be listed here, and you can sign them out from any of them."
          />
        }
        renderItem={({ item }) => {
          // `is_current` is the server's answer for the browser session making
          // the request, which is never this app. For an app install the
          // device uuid is the reliable comparison.
          const isThis = item.id !== undefined && item.id === thisDevice;
          const busy = revoke.isPending && revoke.variables === item.id;

          return (
            <View style={styles.row}>
              <View style={styles.rowText}>
                <Text style={styles.rowTitle}>
                  {describe(item)}
                  {isThis ? ' · This device' : ''}
                </Text>
                <Text style={styles.rowMeta}>
                  {item.kind === 'browser' ? 'Web browser' : 'Jambo app'}
                  {item.platform === 'android_tv' ? ' · TV' : ''}
                </Text>
              </View>

              {isThis ? (
                // Sign-out for this device lives on the Account screen, where
                // it clears the local session too. A "sign out" here would
                // revoke the token and leave the app holding a dead one.
                <Caption>In use</Caption>
              ) : (
                <Button
                  label="Sign out"
                  tone="quiet"
                  busy={busy}
                  onPress={() => {
                    if (item.id !== undefined) confirmRevoke(item, item.id);
                  }}
                />
              )}
            </View>
          );
        }}
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { paddingVertical: spacing.lg },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: spacing.lg,
    gap: spacing.lg,
  },
  rowText: { flex: 1 },
  rowTitle: { ...typography.body, color: colors.text },
  rowMeta: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: spacing.xs,
  },
});
