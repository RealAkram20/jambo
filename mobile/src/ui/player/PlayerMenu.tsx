import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Check } from 'phosphor-react-native';

import { Focusable } from '../rails/Focusable';
import { colors, fonts, player, radius, spacing } from '../theme';
import type { Rendition, VideoQualityPreference } from './playback';

/**
 * The settings popover: quality and playback speed.
 *
 * It is the website's own menu, ported. `public/frontend/js/jambo-settings-menu.js`
 * builds exactly this on the watch page — a list of rows, a tick on the
 * active one — and its colours and metrics are captured in the `player` token
 * block from the rendered page.
 *
 * **There is no subtitles entry, and that is a deliberate deviation from the
 * brief.** No subtitle or caption track exists anywhere in the Streaming or
 * Content modules, so the menu would open onto an empty list. A viewer who
 * taps "Subtitles", sees nothing, and closes the menu concludes they have
 * turned something on. Raised with Rio rather than taken quietly; it becomes
 * a real row the day a caption file exists.
 *
 * **Quality writes to the account, not to this handset.** `/account/preferences`
 * is where `video_quality` lives, which is what makes a choice made on a phone
 * true on the television later. The alternative — a local override — is a
 * second source of truth that silently disagrees with the Streaming
 * preferences screen.
 */

export type PlayerMenuProps = {
  quality: VideoQualityPreference;
  /** Which renditions this title actually has. Absent ones are not offered. */
  available: readonly string[];
  onQuality: (quality: VideoQualityPreference) => void;
  speed: number;
  onSpeed: (speed: number) => void;
  onClose: () => void;
};

/*
 * `auto` first because it is the right answer for most people: it spends the
 * default rendition on wifi and the small one on a bundle, without anyone
 * having to think about it.
 *
 * The two below it are the two files that exist. There is no 1080p row, and
 * there must not be one until there is a third rendition to put behind it.
 */
const QUALITIES: { value: VideoQualityPreference; label: string; detail: string; needs?: Rendition }[] = [
  { value: 'auto', label: 'Auto', detail: 'Data saver on mobile data' },
  { value: 'high', label: 'Default', detail: 'Full quality' },
  { value: 'data_saver', label: 'Data saver', detail: 'Uses less of your bundle', needs: 'low' },
];

const SPEEDS = [0.5, 0.75, 1, 1.25, 1.5, 2];

export function PlayerMenu({
  quality,
  available,
  onQuality,
  speed,
  onSpeed,
  onClose,
}: PlayerMenuProps) {
  const [pane, setPane] = useState<'main' | 'quality' | 'speed'>('main');

  const activeQuality = QUALITIES.find((q) => q.value === quality);

  return (
    <View style={styles.sheet} accessibilityViewIsModal>
      <ScrollView bounces={false}>
        {pane === 'main' ? (
          <>
            <Row
              label="Quality"
              value={activeQuality?.label ?? 'Auto'}
              onPress={() => setPane('quality')}
              hasTVPreferredFocus
            />
            <Row
              label="Playback speed"
              value={speed === 1 ? 'Normal' : `${speed}x`}
              onPress={() => setPane('speed')}
            />
          </>
        ) : null}

        {pane === 'quality' ? (
          <>
            <BackRow label="Quality" onPress={() => setPane('main')} />
            {QUALITIES.map((option) => {
              /*
               * A title with no low rendition does not offer Data saver at
               * all. The server refuses that request with CONTENT_UNAVAILABLE
               * rather than quietly serving the full-size file, so offering
               * the row would be offering a control that cannot complete.
               */
              const missing = option.needs !== undefined && !available.includes(option.needs);
              if (missing) return null;

              return (
                <OptionRow
                  key={option.value}
                  label={option.label}
                  detail={option.detail}
                  selected={option.value === quality}
                  onPress={() => onQuality(option.value)}
                />
              );
            })}
          </>
        ) : null}

        {pane === 'speed' ? (
          <>
            <BackRow label="Playback speed" onPress={() => setPane('main')} />
            {SPEEDS.map((value) => (
              <OptionRow
                key={value}
                label={value === 1 ? 'Normal' : `${value}x`}
                selected={value === speed}
                onPress={() => onSpeed(value)}
              />
            ))}
          </>
        ) : null}
      </ScrollView>

      {/*
        Always reachable, by remote as well as by touch. `apple-design` puts it
        plainly: every screen answers "how do I get out". A menu whose only
        exit is a tap on the scrim traps anyone holding a d-pad.
      */}
      <Focusable accessibilityLabel="Close settings" onPress={onClose} ringRadius={radius.card}>
        <View style={styles.close}>
          <Text style={styles.closeText}>Close</Text>
        </View>
      </Focusable>
    </View>
  );
}

function Row({
  label,
  value,
  onPress,
  hasTVPreferredFocus = false,
}: {
  label: string;
  value: string;
  onPress: () => void;
  hasTVPreferredFocus?: boolean;
}) {
  return (
    <Focusable
      accessibilityLabel={`${label}, ${value}`}
      accessibilityHint="Opens the options"
      onPress={onPress}
      ringRadius={radius.input}
      hasTVPreferredFocus={hasTVPreferredFocus}
    >
      <View style={styles.row}>
        <Text style={styles.label}>{label}</Text>
        <Text style={styles.value}>{value}</Text>
      </View>
    </Focusable>
  );
}

function BackRow({ label, onPress }: { label: string; onPress: () => void }) {
  return (
    <Focusable
      accessibilityLabel={`Back to settings from ${label}`}
      onPress={onPress}
      ringRadius={radius.input}
      hasTVPreferredFocus
    >
      <View style={[styles.row, styles.backRow]}>
        <Text style={styles.backLabel}>{label}</Text>
      </View>
    </Focusable>
  );
}

function OptionRow({
  label,
  detail,
  selected,
  onPress,
}: {
  label: string;
  detail?: string | undefined;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Focusable
      accessibilityLabel={detail === undefined ? label : `${label}, ${detail}`}
      accessibilityRole="button"
      onPress={onPress}
      ringRadius={radius.input}
    >
      <View style={styles.row}>
        {/*
          The tick occupies its space whether or not it is drawn, so choosing a
          different option does not shift every label sideways. State is shown
          by an icon and not by colour alone, which is the accessibility rule
          the `screen` skill names.
        */}
        <View style={styles.tick}>
          {selected ? <Check size={16} color={player.menuFg} weight="bold" /> : null}
        </View>
        <View style={styles.optionText}>
          <Text style={[styles.label, selected && styles.labelSelected]}>{label}</Text>
          {detail === undefined ? null : <Text style={styles.detail}>{detail}</Text>}
        </View>
      </View>
    </Focusable>
  );
}

const styles = StyleSheet.create({
  sheet: {
    minWidth: 236,
    maxWidth: 320,
    maxHeight: 280,
    backgroundColor: player.menuBg,
    borderRadius: player.menuRadius,
    paddingVertical: spacing.xs,
    overflow: 'hidden',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: player.menuRowPadX,
    paddingVertical: player.menuRowPadY,
    gap: spacing.sm,
  },
  backRow: {
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.divider,
  },
  backLabel: {
    color: player.menuFg,
    fontSize: player.menuLabelSize,
    fontFamily: fonts.medium,
  },
  label: {
    flex: 1,
    color: player.menuFg,
    fontSize: player.menuLabelSize,
    fontFamily: fonts.regular,
  },
  labelSelected: { fontFamily: fonts.medium },
  value: {
    color: player.menuValueColor,
    fontSize: player.menuLabelSize,
    fontFamily: fonts.regular,
  },
  detail: {
    color: player.menuValueColor,
    fontSize: player.menuLabelSize - 2,
    fontFamily: fonts.regular,
    marginTop: 1,
  },
  tick: { width: 18, alignItems: 'center' },
  optionText: { flex: 1 },
  close: {
    paddingHorizontal: player.menuRowPadX,
    paddingVertical: player.menuRowPadY,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.divider,
    alignItems: 'center',
  },
  closeText: {
    color: player.menuValueColor,
    fontSize: player.menuLabelSize,
    fontFamily: fonts.medium,
  },
});
