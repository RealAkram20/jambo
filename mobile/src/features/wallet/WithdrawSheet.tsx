import { useState } from 'react';
import { KeyboardAvoidingView, Modal, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { api } from '../../api/jambo';
import { ApiError, NetworkError } from '../../api/errors';
import { Alert, Button, Field } from '../../ui/components';
import { PhoneField } from '../../ui/PhoneField';
import { isMobile, toE164 } from '../../ui/phone';
import { colors, fonts, spacing, wallet as t } from '../../ui/theme';
import { money } from './money';

/**
 * The withdrawal form, as a sheet over the wallet.
 *
 * It is the website's own form — amount, recipient name, mobile money number —
 * and it posts to an endpoint that runs the same service the website's form
 * runs. Nothing about the money is decided here.
 *
 * **Three things this deliberately does not do**, and each is a way a money
 * form goes wrong:
 *
 * 1. **It does not retry.** Not on a timeout, not on a network error, not
 *    behind the scenes. The server refuses a second open request, so a retry
 *    would report an error for a withdrawal that had already succeeded. The
 *    sheet closes and the wallet refetches, which is the only honest way to
 *    find out what happened.
 * 2. **It does not validate the amount against the minimum itself.** The
 *    button is disabled until the server's own `min_withdrawal` is met, and
 *    the server checks it again. A second copy of that rule in here would be a
 *    second place for it to drift.
 * 3. **It does not pre-fill the amount with the balance.** A default that
 *    happens to be the largest possible withdrawal is a default nobody chose.
 */
export function WithdrawSheet({
  visible,
  balance,
  currency,
  onClose,
}: {
  visible: boolean;
  balance: string;
  currency: string;
  onClose: () => void;
}) {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  const [amount, setAmount] = useState('');
  const [name, setName] = useState('');
  const [msisdn, setMsisdn] = useState('');
  const [failed, setFailed] = useState<string | null>(null);

  const reset = () => {
    setAmount('');
    setName('');
    setMsisdn('');
    setFailed(null);
  };

  const request = useMutation({
    mutationFn: () =>
      api.requestWithdrawal({
        amount: amount.trim(),
        payee_name: name.trim(),
        // E.164, so the row the clerk actions carries one unambiguous
        // number rather than whatever shape it was typed in.
        payee_msisdn: toE164(msisdn) ?? msisdn.trim(),
      }),
    onSuccess: () => {
      // Refetch rather than patch the cache: the hold, the balance and the new
      // withdrawal row all move together on the server, and reassembling that
      // here would be three chances to disagree with it.
      void queryClient.invalidateQueries({ queryKey: ['wallet'] });
      reset();
      onClose();
    },
    onError: (error) => {
      setFailed(
        error instanceof NetworkError
          ? // Deliberately not "try again". It may have gone through.
            'Could not reach Jambo. Close this and check your wallet before asking again.'
          : error instanceof ApiError && error.message !== ''
            ? // The server's own words: the minimum, the one already in
              // progress, or the insufficient balance. All three are answers.
              error.message
            : 'That withdrawal was not requested.',
      );
    },
  });

  /*
   * The number is checked before the button is offered, not after the request
   * fails. The server checks it too — this is a courtesy, not the guard — but
   * a viewer who has mistyped a payout number should learn it while they are
   * still looking at the field.
   */
  const msisdnError =
    msisdn.trim() === '' || isMobile(msisdn)
      ? null
      : 'That is not a mobile number that can receive money.';

  const ready =
    amount.trim() !== '' && name.trim() !== '' && msisdn.trim() !== '' && msisdnError === null;

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      // What makes the Android back button dismiss the sheet rather than the
      // screen behind it. Same reason the sort sheet and CountryPicker use it.
      onRequestClose={() => {
        if (!request.isPending) onClose();
      }}
    >
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Close"
        style={styles.scrim}
        onPress={() => {
          if (!request.isPending) onClose();
        }}
      />

      {/*
        `flex: 1` and `box-none` together, and both are load-bearing.

        Without the flex the wrapper shrinks to its content, so the sheet's
        `marginTop: 'auto'` has nothing to push against and the whole thing
        renders against the top of the screen — which is what the first cut did
        on the emulator. Without `box-none` the full-height wrapper swallows
        every touch and the scrim behind it stops dismissing the sheet.
      */}
      <KeyboardAvoidingView
        style={styles.fill}
        pointerEvents="box-none"
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <View style={[styles.sheet, { paddingBottom: Math.max(spacing.xl, insets.bottom) }]}>
          <View style={styles.handle} />

          <ScrollView keyboardShouldPersistTaps="handled">
            <Text accessibilityRole="header" style={styles.title}>
              Withdraw
            </Text>
            {/* A real constraint, and the only number the form cannot show. */}
            <Text style={styles.available}>{money(balance, currency)} available</Text>

            {failed !== null ? <Alert tone="error">{failed}</Alert> : null}

            <Field
              label="Amount"
              value={amount}
              onChangeText={setAmount}
              keyboardType="number-pad"
              editable={!request.isPending}
            />

            <Field
              label="Recipient name"
              value={name}
              onChangeText={setName}
              autoCapitalize="words"
              editable={!request.isPending}
            />

            {/*
              **The one field on this screen where a typo costs money.** It is
              checked as a real mobile line rather than only as a valid number:
              a Ugandan landline parses perfectly and cannot receive mobile
              money, so "valid" is not the question this field has to answer.
            */}
            <PhoneField
              label="Mobile money number"
              value={msisdn}
              onChangeText={setMsisdn}
              error={msisdnError}
              hint="The number the money is sent to."
            />

            <Button
              label={request.isPending ? 'Requesting…' : 'Request withdrawal'}
              busy={request.isPending}
              disabled={!ready || request.isPending}
              onPress={() => request.mutate()}
            />

            {/*
              The one sentence on this sheet. It carries a fact the form cannot
              show: the money leaves the balance now and arrives later, so a
              viewer who sees their balance drop has been told why in advance
              rather than after.
            */}
            <Text style={styles.note}>Held from your balance until it is paid.</Text>
          </ScrollView>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  fill: { flex: 1 },
  scrim: { position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: t.sheetScrim },
  sheet: {
    marginTop: 'auto',
    backgroundColor: colors.surface,
    borderTopLeftRadius: t.sheetRadius,
    borderTopRightRadius: t.sheetRadius,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    maxHeight: '90%',
  },
  handle: {
    alignSelf: 'center',
    width: 38,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.border,
    marginBottom: spacing.lg,
  },
  title: { fontFamily: fonts.bold, fontSize: 20, color: colors.text },
  available: {
    fontFamily: fonts.regular,
    fontSize: t.metaSize,
    color: t.metaColor,
    marginTop: 2,
    marginBottom: spacing.lg,
  },
  note: {
    fontFamily: fonts.regular,
    fontSize: t.metaSize,
    color: t.metaColor,
    marginTop: spacing.md,
    textAlign: 'center',
  },
});
