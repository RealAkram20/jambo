import { useCallback, useState } from 'react';
import {
  Clipboard,
  Linking,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { LinearGradient } from 'expo-linear-gradient';
import {
  ArrowRight,
  Copy,
  DotsThree,
  PencilSimple,
  FacebookLogo,
  Gift,
  Link as LinkIcon,
  User,
  UserPlus,
  Wallet,
  WhatsappLogo,
  XLogo,
} from 'phosphor-react-native';

import { api } from '../../api/jambo';
import type { ReferralDashboard } from '../../api/endpoints';
import { ApiError } from '../../api/errors';
import { EmptyState, ErrorState, Loading } from '../../ui/components';
import { Focusable } from '../../ui/rails/Focusable';
import { colors, fonts, referrals as t, spacing } from '../../ui/theme';
import { EditCodeSheet } from './EditCodeSheet';
import { count, money, percent, shareMessage, shareUrlFor, termsLine } from './share';

/**
 * Refer & Earn, from Rio's mockup of 2026-09-09.
 *
 * **A 404 here is not an error.** The endpoint returns one when the referral
 * programme is switched off — with the exception the spec is explicit about,
 * that a viewer who already has wallet history still gets their dashboard,
 * because money someone earned must not become unreachable because an admin
 * changed a setting. So a 404 renders as "this is not available", calmly, with
 * no Try again button: retrying a switch that is off is not a fix, and
 * offering it says the app thinks something broke. Carried over unchanged from
 * the screen this replaces.
 *
 * **Every figure on it is the website's own.** `ReferralController` and
 * `refer.blade.php` both read `ReferralDashboardService`, so the app and the
 * site cannot report different earnings for the same account.
 *
 * 🔴 **One tile in the mockup was dropped, and it is the money one.** It reads
 * "15 Days Free Premium Earned". Jambo's referral reward is a percentage of
 * what the friend pays, credited as money to a wallet — there is no free-days
 * entitlement in `Modules/Referrals`, in the ledger, or on any subscription
 * tier. Telling someone they have earned fifteen days they cannot redeem is
 * the case rule 1 of the `screen` standard exists for. The tile shows what
 * they actually have, in currency.
 */
export function ReferralsScreen() {
  const [copied, setCopied] = useState<string | null>(null);
  const [editing, setEditing] = useState(false);
  const queryClient = useQueryClient();

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['referrals'],
    queryFn: () => api.referrals(),
    // A 404 is a real answer, not a flake. Retrying it three times just delays
    // the screen the viewer is going to get anyway.
    retry: (attempts, caught) =>
      caught instanceof ApiError && caught.code === 'NOT_FOUND' ? false : attempts < 2,
  });

  /**
   * Copy, and say so.
   *
   * `Clipboard` from react-native core rather than `expo-clipboard`: it is
   * deprecated but present and linked in react-native-tvos 0.86, and the Expo
   * module would need a native rebuild — which would invalidate the dev client
   * other sessions are running against. Worth swapping at the next rebuild;
   * the call site is this one function.
   *
   * The confirmation is the website's: `refer.blade.php` shows "Link copied"
   * and hides it after two seconds.
   */
  const copy = useCallback((value: string, label: string) => {
    Clipboard.setString(value);
    setCopied(label);
    setTimeout(() => setCopied(null), 2000);
  }, []);

  if (isPending) return <Loading label="Loading Refer and Earn" />;

  if (isError) {
    const off = error instanceof ApiError && error.code === 'NOT_FOUND';

    return (
      <View style={styles.screen}>
        {off ? (
          <EmptyState
            title="Refer & Earn is not available"
            // The one sentence that stays: a viewer whose money is involved
            // must not think it went with the programme.
            detail="Anything you have earned is still in your wallet."
          />
        ) : (
          <ErrorState
            message={
              error instanceof Error && error.message !== ''
                ? error.message
                : 'We could not load Refer and Earn.'
            }
            onRetry={() => {
              void refetch();
            }}
          />
        )}
      </View>
    );
  }

  const code = typeof data.code === 'string' && data.code !== '' ? data.code : null;
  const link =
    typeof data.share_url === 'string' && data.share_url.trim() !== ''
      ? data.share_url.trim()
      : null;
  const message = shareMessage(data);
  const terms = termsLine(data);

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.column}>
          <Hero terms={terms} />

          {code === null ? null : (
            <CodeCard
              code={code}
              copiedLabel={copied}
              hasLink={link !== null}
              onCopyCode={() => copy(code, 'code')}
              onCopyLink={() => {
                if (link !== null) copy(link, 'link');
              }}
              onEdit={() => setEditing(true)}
            />
          )}

          {message === null ? null : (
            <ShareRow
              data={data}
              message={message}
              copiedLabel={copied}
              onCopyLink={() => {
                if (link !== null) copy(link, 'link');
              }}
            />
          )}

          <HowItWorks reward={percent(data.terms?.reward_percent)} />

          <Rewards
            qualified={count(data.stats?.qualified)}
            earned={money(data.currency, data.stats?.total_earned)}
          />
        </View>
      </ScrollView>

      {/*
        Editing lives behind a control rather than on the card, because the
        code is read far more often than it is changed — the website puts it
        in its own section for the same reason.
      */}
      {/*
        Mounted only while open, so it seeds from the current code on every
        open without an effect. See its docblock: re-seeding form state from
        an effect is a known input-eating bug in this app.
      */}
      {editing ? (
        <EditCodeSheet
          currentCode={code ?? ''}
          onClose={() => setEditing(false)}
          onSaved={() => {
            // Refetch rather than patch the cache: the code appears in
            // `share_url` too, and the server builds that.
            void queryClient.invalidateQueries({ queryKey: ['referrals'] });
          }}
        />
      ) : null}
    </View>
  );
}

/* ── the pieces ──────────────────────────────────────────────────────── */

/**
 * The hero.
 *
 * A tinted panel with the site's own gift glyph, not an illustration. There is
 * no artwork for this screen anywhere in the product, and a stock image would
 * be the only invented thing on the page.
 *
 * The mockup's third line — "Share Jambo Films with friends and earn rewards
 * when they join" — is gone. It restated the headline above it, which is
 * exactly the copy Rio ruled out. What replaces it carries the two real
 * percentages, which appear nowhere else.
 */
function Hero({ terms }: { terms: string | null }) {
  return (
    <View style={styles.hero}>
      <View style={styles.heroText}>
        <Text style={styles.eyebrow}>SHARE THE ENTERTAINMENT</Text>
        <Text accessibilityRole="header" style={styles.headline}>
          Invite Friends{'\n'}
          <Text style={styles.headlineAccent}>Earn Rewards</Text>
        </Text>
        {terms === null ? null : <Text style={styles.terms}>{terms}</Text>}
      </View>

      {/*
        Decorative, and it needs no `accessible={false}`: a Phosphor icon is
        react-native-svg, which a screen reader does not announce on its own.
        The headline beside it already says what this is.
      */}
      <Gift size={t.giftSize} color={t.giftColor} weight="fill" />
    </View>
  );
}

/**
 * The code, and the two things a viewer can take away with it.
 *
 * **The icon copies the CODE; the button copies the LINK.** Rio, 2026-09-09:
 * *"the copy button should be copy link, i mean the referral link, then copy
 * icon for the code"*, and *"let the button be 'Referral Link'"*. Both used to
 * copy the code, which made one of them pointless.
 *
 * The split matches how the two are actually used. A link is what gets pasted
 * into a chat and carries the referral through signup on its own, so it is the
 * wider, labelled control. A code is what gets read aloud or typed at
 * checkout, so it sits on the code itself as a quiet icon.
 */
function CodeCard({
  code,
  copiedLabel,
  hasLink,
  onCopyCode,
  onCopyLink,
  onEdit,
}: {
  code: string;
  copiedLabel: string | null;
  /** No link, no link button — rather than one that copies an empty string. */
  hasLink: boolean;
  onCopyCode: () => void;
  onCopyLink: () => void;
  onEdit: () => void;
}) {
  return (
    <View style={styles.card}>
      <View style={styles.cardHead}>
        <Text style={styles.cardTitle}>Your Referral Code</Text>
        {/*
          The website lets a viewer set a custom code, with a live availability
          check. The app had no equivalent until now — its only way to learn a
          code was taken was to submit and read a 422.
        */}
        <Focusable
          accessibilityLabel="Change your referral code"
          ringRadius={t.copyIconRadius}
          onPress={onEdit}
          style={styles.edit}
        >
          <PencilSimple size={15} color={t.headlineAccent} weight="bold" />
          <Text style={styles.editText}>Edit</Text>
        </Focusable>
      </View>

      <View style={styles.codeRow}>
        {/*
          Selectable: somebody will want to read it out or copy it by hand,
          and the two copy buttons beside it do not cover that.
        */}
        <Text selectable style={styles.code}>
          {code}
        </Text>

        <Focusable
          accessibilityLabel={`Copy the code ${code}`}
          ringRadius={t.copyIconRadius}
          onPress={onCopyCode}
          style={styles.copyIcon}
        >
          <Copy size={18} color={colors.text} weight="regular" />
        </Focusable>

        {hasLink ? (
          <Focusable
            accessibilityLabel="Copy your referral link"
            ringRadius={t.copyButtonRadius}
            onPress={onCopyLink}
            style={styles.copyButton}
          >
            <LinearGradient
              colors={[t.copyFrom, t.copyTo]}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={StyleSheet.absoluteFill}
            />
            <Text style={styles.copyButtonText}>Referral Link</Text>
          </Focusable>
        ) : null}
      </View>

      {/*
        The confirmation, in the site's own words. It is announced as well as
        shown — a copy that only flashes a colour tells a screen-reader user
        nothing about whether the tap worked.
      */}
      {copiedLabel === 'code' || copiedLabel === 'link' ? (
        <Text accessibilityLiveRegion="polite" style={styles.copied}>
          {copiedLabel === 'code' ? 'Code copied' : 'Link copied'}
        </Text>
      ) : null}
    </View>
  );
}

/**
 * Where a viewer can send it.
 *
 * **Five real destinations, not decoration.** WhatsApp, Facebook and X go
 * through their public web share endpoints, which open the installed app when
 * there is one and the web client when there is not — see `share.ts` for why
 * that is preferred to a `whatsapp://` scheme. Copy uses the clipboard, and
 * More opens the platform's own sheet, which already knows every other app the
 * viewer would send this to.
 *
 * If a destination cannot be opened the button falls back to the OS sheet
 * rather than failing silently, because the viewer's intent was to share.
 */
function ShareRow({
  data,
  message,
  copiedLabel,
  onCopyLink,
}: {
  data: ReferralDashboard;
  message: string;
  copiedLabel: string | null;
  onCopyLink: () => void;
}) {
  const openOrShare = useCallback(
    (url: string | null) => {
      if (url === null) return;
      Linking.openURL(url).catch(() => {
        void Share.share({ message });
      });
    },
    [message],
  );

  return (
    <View style={styles.card}>
      <Text style={styles.cardTitle}>Share via</Text>

      <View style={styles.shareRow}>
        <ShareButton
          label="WhatsApp"
          background={t.whatsapp}
          onPress={() => openOrShare(shareUrlFor('whatsapp', data))}
        >
          <WhatsappLogo size={t.shareIconSize} color="#ffffff" weight="fill" />
        </ShareButton>

        <ShareButton
          label="Facebook"
          background={t.facebook}
          onPress={() => openOrShare(shareUrlFor('facebook', data))}
        >
          <FacebookLogo size={t.shareIconSize} color="#ffffff" weight="fill" />
        </ShareButton>

        <ShareButton
          label="X"
          background={t.x}
          bordered
          onPress={() => openOrShare(shareUrlFor('x', data))}
        >
          <XLogo size={t.shareIconSize} color="#ffffff" weight="fill" />
        </ShareButton>

        <ShareButton label="Copy Link" background={t.neutralShareBg} onPress={onCopyLink}>
          <LinkIcon size={t.shareIconSize} color={colors.text} weight="bold" />
        </ShareButton>

        <ShareButton
          label="More"
          background={t.neutralShareBg}
          onPress={() => {
            void Share.share({ message });
          }}
        >
          <DotsThree size={t.shareIconSize} color={colors.text} weight="bold" />
        </ShareButton>
      </View>

      {copiedLabel === 'link' ? (
        <Text accessibilityLiveRegion="polite" style={styles.copied}>
          Link copied
        </Text>
      ) : null}
    </View>
  );
}

function ShareButton({
  label,
  background,
  bordered = false,
  onPress,
  children,
}: {
  label: string;
  background: string;
  bordered?: boolean;
  onPress: () => void;
  children: React.ReactNode;
}) {
  return (
    <View style={styles.shareItem}>
      <Focusable
        // "Share on WhatsApp", not "WhatsApp": the label alone reads as a
        // destination rather than as an action.
        accessibilityLabel={label === 'Copy Link' ? 'Copy the link' : `Share on ${label}`}
        ringRadius={t.shareSize / 2}
        onPress={onPress}
        style={[
          styles.sharePlate,
          { backgroundColor: background },
          bordered ? styles.sharePlateBordered : null,
        ]}
      >
        {children}
      </Focusable>
      <Text numberOfLines={1} style={styles.shareLabel}>
        {label}
      </Text>
    </View>
  );
}

/**
 * How It Works.
 *
 * The mockup's third caption is "Get rewards automatically", which says
 * nothing a viewer could act on. It is replaced by the actual reward, which
 * the server sends — and when it does not, the step keeps its title and drops
 * the line rather than falling back to the vague version.
 */
function HowItWorks({ reward }: { reward: string | null }) {
  const steps = [
    { icon: UserPlus, title: 'Invite', detail: 'Share your code' },
    { icon: User, title: 'They Join', detail: 'They sign up and subscribe' },
    {
      icon: Gift,
      title: 'You Earn',
      detail: reward === null ? null : `${reward} of what they pay`,
    },
  ];

  return (
    <View style={styles.card}>
      <Text style={styles.cardTitle}>How It Works</Text>

      <View style={styles.steps}>
        {steps.map((step, index) => (
          <View key={step.title} style={styles.stepPair}>
            <View style={styles.step}>
              <View style={styles.stepPlate}>
                <step.icon size={t.stepIconSize} color={t.stepIcon} weight="fill" />
              </View>
              <Text style={styles.stepTitle}>{`${index + 1}. ${step.title}`}</Text>
              {step.detail === null ? null : (
                <Text style={styles.stepDetail}>{step.detail}</Text>
              )}
            </View>

            {/* Decorative: the numbers already carry the order. */}
            {index < steps.length - 1 ? (
              <ArrowRight size={14} color={t.arrowColor} weight="bold" style={styles.arrow} />
            ) : null}
          </View>
        ))}
      </View>
    </View>
  );
}

/**
 * Your Rewards.
 *
 * Two figures, both real. The mockup's second was "15 Days Free Premium
 * Earned"; Jambo pays a percentage into a wallet and has no free-days
 * entitlement anywhere, so this shows the money that actually exists. See the
 * screen's docblock.
 */
function Rewards({ qualified, earned }: { qualified: string; earned: string }) {
  return (
    <View style={styles.card}>
      <Text style={styles.cardTitle}>Your Rewards</Text>

      <View style={styles.rewards}>
        <View style={styles.reward}>
          <Gift size={t.rewardIconSize} color={t.stepIcon} weight="fill" />
          <View style={styles.rewardText}>
            <Text style={styles.rewardValue}>{qualified}</Text>
            {/* The server's own word for a referral that has subscribed. */}
            <Text style={styles.rewardLabel}>Subscribed referrals</Text>
          </View>
        </View>

        <View style={styles.rewardDivider} />

        <View style={styles.reward}>
          {/* The site's own glyph for this idea: refer.blade.php links to the
              wallet with `ph-wallet`, and this is the figure that lands in it. */}
          <Wallet size={t.rewardIconSize} color={t.stepIcon} weight="fill" />
          <View style={styles.rewardText}>
            <Text style={styles.rewardValue}>{earned}</Text>
            <Text style={styles.rewardLabel}>Earned in total</Text>
          </View>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, paddingBottom: spacing.xxl },
  // Capped and centred, so a tablet gets a column rather than a very wide row
  // of three steps. Same reasoning as `auth.cardMaxWidth`.
  column: { width: '100%', maxWidth: t.maxWidth, alignSelf: 'center', gap: t.cardGap },

  hero: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: t.heroBg,
    borderColor: t.heroBorder,
    borderWidth: 1,
    borderRadius: t.heroRadius,
    padding: spacing.xl,
  },
  heroText: { flex: 1, gap: spacing.sm },
  eyebrow: {
    fontFamily: fonts.medium,
    fontSize: t.eyebrowSize,
    letterSpacing: t.eyebrowTracking,
    color: t.eyebrowColor,
  },
  headline: {
    fontFamily: fonts.black,
    fontSize: t.headlineSize,
    lineHeight: t.headlineLineHeight,
    color: colors.text,
  },
  headlineAccent: { color: t.headlineAccent },
  terms: {
    fontFamily: fonts.regular,
    fontSize: 12,
    lineHeight: 18,
    color: t.labelColor,
  },

  card: {
    backgroundColor: t.cardBg,
    borderColor: t.cardBorder,
    borderWidth: 1,
    borderRadius: t.cardRadius,
    padding: t.cardPad,
    gap: spacing.md,
  },
  cardHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  cardTitle: { fontFamily: fonts.medium, fontSize: t.cardTitleSize, color: colors.text },
  edit: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.xs,
    paddingHorizontal: spacing.sm,
    borderRadius: t.copyIconRadius,
  },
  editText: { fontFamily: fonts.medium, fontSize: 13, color: t.headlineAccent },

  codeRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  code: {
    flex: 1,
    fontFamily: fonts.bold,
    fontSize: t.codeSize,
    letterSpacing: t.codeTracking,
    color: t.codeColor,
  },
  copyIcon: {
    width: t.copyIconSize,
    height: t.copyIconSize,
    borderRadius: t.copyIconRadius,
    backgroundColor: t.copyIconBg,
    borderWidth: 1,
    borderColor: t.copyIconBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  copyButton: {
    height: t.copyButtonHeight,
    paddingHorizontal: spacing.xl,
    borderRadius: t.copyButtonRadius,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  copyButtonText: { fontFamily: fonts.medium, fontSize: 14, color: colors.onPrimary },
  copied: { fontFamily: fonts.regular, fontSize: 12, color: t.copiedColor },

  shareRow: { flexDirection: 'row', justifyContent: 'space-between', gap: t.shareGap },
  shareItem: { flex: 1, alignItems: 'center', gap: spacing.sm },
  sharePlate: {
    width: t.shareSize,
    height: t.shareSize,
    borderRadius: t.shareSize / 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  // X's black plate needs an outline or it is a hole in a dark card.
  sharePlateBordered: { borderWidth: 1, borderColor: t.xBorder },
  shareLabel: {
    fontFamily: fonts.regular,
    fontSize: t.shareLabelSize,
    color: t.labelColor,
    textAlign: 'center',
  },

  steps: { flexDirection: 'row', alignItems: 'flex-start' },
  stepPair: { flex: 1, flexDirection: 'row', alignItems: 'flex-start' },
  step: { flex: 1, alignItems: 'center', gap: spacing.sm },
  stepPlate: {
    width: t.stepSize,
    height: t.stepSize,
    borderRadius: t.stepSize / 2,
    backgroundColor: t.stepBg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepTitle: {
    fontFamily: fonts.medium,
    fontSize: t.stepTitleSize,
    color: colors.text,
    textAlign: 'center',
  },
  stepDetail: {
    fontFamily: fonts.regular,
    fontSize: t.stepDetailSize,
    lineHeight: 15,
    color: t.labelColor,
    textAlign: 'center',
  },
  // Sits level with the icon plates rather than with the text below them.
  arrow: { marginTop: t.stepSize / 2 - 7 },

  rewards: { flexDirection: 'row', alignItems: 'center' },
  reward: { flex: 1, flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  rewardText: { flex: 1 },
  rewardValue: { fontFamily: fonts.bold, fontSize: t.rewardValueSize, color: colors.text },
  rewardLabel: { fontFamily: fonts.regular, fontSize: t.rewardLabelSize, color: t.labelColor },
  rewardDivider: {
    width: 1,
    alignSelf: 'stretch',
    backgroundColor: t.rewardDivider,
    marginHorizontal: spacing.md,
  },
});
