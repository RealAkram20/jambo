import {
  Bell,
  BellRinging,
  BookmarkSimple,
  ChatCircleDots,
  CheckCircle,
  Coins,
  CreditCard,
  CrownSimple,
  Desktop,
  DeviceMobile,
  DownloadSimple,
  EnvelopeOpen,
  FilmSlate,
  FilmStrip,
  Flag,
  Gift,
  Globe,
  HandCoins,
  HandWaving,
  HourglassMedium,
  Key,
  Megaphone,
  Money,
  PlayCircle,
  Prohibit,
  Receipt,
  SealCheck,
  ShieldCheck,
  Stack,
  Star,
  Television,
  UserCircle,
  UserMinus,
  UserPlus,
  WarningOctagon,
  XCircle,
  type IconProps,
} from 'phosphor-react-native';
import type { ComponentType } from 'react';

/**
 * The website's icon name for a notification, resolved to the same glyph.
 *
 * `GET /notifications` returns `icon` as a Phosphor class — `ph-film-strip`,
 * `ph-device-mobile` — because that is what the notification classes have
 * always written and what `profile-hub/notifications.blade.php` renders. The
 * app draws the same icon set, so a movie notification is the same film strip
 * on both surfaces without anybody maintaining a second opinion about it.
 *
 * **Named imports rather than a lookup on the module.** Every name here could
 * be derived — strip `ph-`, split on the hyphen, capitalise — and a two-line
 * dynamic lookup would need no maintenance at all. It would also pull all
 * ~1,250 Phosphor icons into the bundle, because nothing could then be shaken
 * out. These are the 34 the notifications module actually sends, which is what
 * `grep -rhoE "ph-[a-z0-9-]+" Modules/Notifications/app/` returns, and
 * `NotificationCategoryApiTest` fails if the backend starts sending one that
 * is not here.
 *
 * **It is no longer only the inbox's map.** The devices screen resolves the
 * icon `UserAgent::parse` picks for a browser row through the same table —
 * `ph-desktop` and `ph-globe` are here for it. One map rather than two,
 * because two would drift the first time either gained an entry.
 */
const ICONS: Readonly<Record<string, ComponentType<IconProps>>> = {
  'ph-bell': Bell,
  'ph-bell-ringing': BellRinging,
  'ph-bookmark-simple': BookmarkSimple,
  'ph-chat-circle-dots': ChatCircleDots,
  'ph-check-circle': CheckCircle,
  'ph-coins': Coins,
  'ph-credit-card': CreditCard,
  'ph-crown-simple': CrownSimple,
  'ph-desktop': Desktop,
  'ph-device-mobile': DeviceMobile,
  'ph-download-simple': DownloadSimple,
  'ph-envelope-open': EnvelopeOpen,
  'ph-film-slate': FilmSlate,
  'ph-film-strip': FilmStrip,
  'ph-flag': Flag,
  'ph-gift': Gift,
  'ph-globe': Globe,
  'ph-hand-coins': HandCoins,
  'ph-hand-waving': HandWaving,
  'ph-hourglass-medium': HourglassMedium,
  'ph-key': Key,
  'ph-megaphone': Megaphone,
  'ph-money': Money,
  'ph-play-circle': PlayCircle,
  'ph-prohibit': Prohibit,
  'ph-receipt': Receipt,
  'ph-seal-check': SealCheck,
  'ph-shield-check': ShieldCheck,
  'ph-stack': Stack,
  'ph-star': Star,
  'ph-television': Television,
  'ph-user-circle': UserCircle,
  'ph-user-minus': UserMinus,
  'ph-user-plus': UserPlus,
  'ph-warning-octagon': WarningOctagon,
  'ph-x-circle': XCircle,
};

/** Whether the map has a glyph for this name, without resolving it. */
export function hasIcon(name: string | null | undefined): boolean {
  return typeof name === 'string' && name !== '' && name in ICONS;
}

/**
 * The glyph for an icon name, falling back to the bell.
 *
 * A notification the app has no icon for is still a notification, so the
 * fallback is the one glyph that is true of every row rather than a blank
 * square or a question mark.
 */
export function iconFor(name: string | null | undefined): ComponentType<IconProps> {
  if (typeof name !== 'string') return Bell;

  return ICONS[name] ?? Bell;
}

/** Exposed so the drift test can read the same list the app draws from. */
export const KNOWN_ICONS: readonly string[] = Object.keys(ICONS);
