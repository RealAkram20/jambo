import {
  AsYouType as CoreAsYouType,
  getCountryCallingCode as coreGetCountryCallingCode,
  parsePhoneNumberFromString as coreParse,
  type CountryCode,
} from 'libphonenumber-js/core';
import metadata from 'libphonenumber-js/metadata.max.json';

/*
 * `core` plus the metadata as a plain JSON import, rather than
 * `libphonenumber-js/max`.
 *
 * **The convenient import does not bundle.** `max/exports/AsYouType.js` reaches
 * for `../../metadata.max.json.js` — a shim the package ships because Node's
 * ESM loader cannot import JSON — and Metro cannot resolve that double
 * extension. The app built, launched, and died on the dev launcher's error
 * screen with `Unable to resolve "../../metadata.max.json.js"`.
 *
 * The `core` entry point takes metadata as an argument instead, and Metro
 * imports `.json` natively. Same library, same metadata, one indirection
 * fewer. The three wrappers below exist only to bind that argument once so no
 * caller has to remember it.
 */

const parsePhoneNumberFromString = (text: string, country?: CountryCode) =>
  /*
   * The options key is omitted rather than set to `undefined`.
   *
   * `exactOptionalPropertyTypes` is on in this project, so
   * `{ defaultCountry: undefined }` is not the same as `{}` and the library's
   * own types reject it. That strictness is right: the two mean different
   * things, and the case that needs the empty object is real —
   * `formatForDisplay` parses an E.164 number that already carries its own
   * country and must not be given a default that could override it.
   */
  coreParse(text, country === undefined ? {} : { defaultCountry: country }, metadata);

const getCountryCallingCode = (country: CountryCode) =>
  coreGetCountryCallingCode(country, metadata);

class AsYouType extends CoreAsYouType {
  constructor(country?: CountryCode) {
    super(country, metadata);
  }
}

/**
 * Phone numbers: recognised however they are typed, stored one way, shown one
 * way.
 *
 * Rio, 2026-09-10, with fifteen worked examples of the same Ugandan number —
 * `+256742078673`, `0742 078 673`, `00256 742 078 673`, `(0742) 078 673`,
 * `742-078-673` and the rest. The app must read all of them, carry a dial code
 * from the selected country, and **format live as the number is typed, the way
 * a card number does.**
 *
 * **`libphonenumber-js/max`, by his decision, and the reason is the withdrawal
 * field.** Every format he listed differs only in separators and the
 * international prefix, so *recognising* them needs no library at all. What
 * needs one is knowing that `742078673` is a real Ugandan mobile line, that
 * `0123456789` is not, and that `0414230000` is a landline — because one of
 * the two fields this feeds is a **mobile money payout number**, where a
 * plausible typo sends money to somebody else. `max` metadata is what carries
 * line types; `min` parses but answers `undefined` to "is this a mobile".
 *
 * **Stored as E.164 and nothing else.** `+256742078673` is the one form that is
 * unambiguous, comparable and dialable. Everything a viewer types converges on
 * it; everything a screen shows is derived from it.
 */

/** The app's home market, and the assumption a bare `0742…` is read against. */
export const DEFAULT_COUNTRY: CountryCode = 'UG';

/**
 * The grouping Rio asked for, which is NOT the library's.
 *
 * `AsYouType('UG')` produces `0742 078673` and `+256 742 078673` — 4 then 6 —
 * because that is the official national convention in Google's metadata. Rio's
 * examples are `0742 078 673` and `+256 742 078 673`, in threes. **His examples
 * are the specification**, so Uganda is overridden here and every other country
 * keeps the library's own rules, where the library is the only spec we have.
 *
 * One entry, easily extended, and deliberately not a global override: forcing
 * threes everywhere would render a US number as `+1 555 123 456 7`.
 */
const GROUPING: Partial<Record<CountryCode, { national: number[]; international: number[] }>> = {
  UG: { national: [4, 3, 3], international: [3, 3, 3] },
};

/** "+256" for UG. Empty when the country is unknown rather than a guess. */
export function dialCode(country: string | null | undefined): string {
  if (typeof country !== 'string' || country.length !== 2) return '';

  try {
    return `+${getCountryCallingCode(country.toUpperCase() as CountryCode)}`;
  } catch {
    // A code the library does not know. The picker offers every assigned
    // ISO-3166 code and a few of them have no calling code of their own.
    return '';
  }
}

/**
 * Anything a viewer might type, as E.164 — or null when it cannot be read.
 *
 * Null is a real answer and callers must render it as "no number" rather than
 * as an empty string, which the server would store as a phone number of no
 * digits.
 */
export function toE164(input: string, country: string = DEFAULT_COUNTRY): string | null {
  const trimmed = input.trim();
  if (trimmed === '') return null;

  /*
   * `00` is the international access prefix in Uganda and most of the world,
   * and libphonenumber only accepts it when it knows which country is dialling
   * out. Rio's list includes `00256742078673`, so it is rewritten to `+` here
   * rather than left to fail. `011` is the same thing in North America and is
   * deliberately NOT handled: this app has no North American market and a
   * wrong guess about a leading `011` is worse than a rejection.
   */
  const normalised = /^00\d/.test(trimmed.replace(/[^\d+]/g, ''))
    ? `+${trimmed.replace(/[^\d+]/g, '').slice(2)}`
    : trimmed;

  const parsed = parsePhoneNumberFromString(normalised, asCountry(country));

  return parsed?.isValid() === true ? parsed.number : null;
}

/** Whether this is a real mobile line, which is what a payout needs. */
export function isMobile(input: string, country: string = DEFAULT_COUNTRY): boolean {
  const parsed = parsePhoneNumberFromString(input.trim(), asCountry(country));
  if (parsed?.isValid() !== true) return false;

  const type = parsed.getType();

  /*
   * `MOBILE` and `FIXED_LINE_OR_MOBILE` both pass. The second is what the
   * metadata says when a country's numbering plan does not separate the two,
   * and refusing it would reject every number in those countries — a stricter
   * rule that is wrong more often than it is right.
   */
  return type === 'MOBILE' || type === 'FIXED_LINE_OR_MOBILE';
}

/**
 * The number as it should look while it is being typed.
 *
 * Progressive, like a card field: each keystroke returns the whole value with
 * separators in place, so the caller sets it straight back into the input.
 *
 * **Digits are never added, removed or reordered** — only spacing. A formatter
 * that drops a character a viewer typed is a formatter that loses a phone
 * number, and it is invisible until somebody cannot be reached.
 */
export function formatAsTyped(input: string, country: string = DEFAULT_COUNTRY): string {
  const code = asCountry(country);
  const digitsOnly = input.replace(/[^\d+]/g, '');
  if (digitsOnly === '' || digitsOnly === '+') return digitsOnly;

  const custom = code === undefined ? undefined : GROUPING[code];

  if (custom !== undefined) {
    return groupBy(digitsOnly, custom);
  }

  // Every other country: the library's own national convention.
  const typer = new AsYouType(code);
  return typer.input(digitsOnly);
}

/**
 * The stored number, as a screen should show it.
 *
 * International, because a stored number is E.164 and the country it belongs
 * to is part of what it says. Falls back to the raw value rather than blanking
 * it: a number that predates this file and cannot be parsed is still the only
 * number that account has, and hiding it would read as "no phone".
 */
export function formatForDisplay(e164: string | null | undefined): string {
  if (typeof e164 !== 'string' || e164.trim() === '') return '';

  const parsed = parsePhoneNumberFromString(e164.trim());
  if (parsed?.isValid() !== true) return e164.trim();

  const custom = parsed.country === undefined ? undefined : GROUPING[parsed.country];
  if (custom === undefined) return parsed.formatInternational();

  return `+${parsed.countryCallingCode} ${groupBy(parsed.nationalNumber, {
    national: custom.international,
    international: custom.international,
  })}`;
}

/* ── internals ───────────────────────────────────────────────────────── */

function asCountry(country: string | null | undefined): CountryCode | undefined {
  if (typeof country !== 'string' || country.length !== 2) return undefined;

  return country.toUpperCase() as CountryCode;
}

/**
 * Space `digits` into the given group sizes, repeating the last size.
 *
 * An international value keeps its `+` and its calling code together, because
 * `+256 742 078 673` is what Rio's examples show — the code is one unit, not
 * the first three digits of the number.
 */
function groupBy(digits: string, sizes: { national: number[]; international: number[] }): string {
  if (digits.startsWith('+')) {
    const body = digits.slice(1);

    // Longest first: +1 must not swallow the start of +1242.
    for (const length of [3, 2, 1]) {
      const code = body.slice(0, length);
      if (code === '' || code.length < length) continue;

      const rest = body.slice(length);
      if (rest === '') return `+${code}`;

      return `+${code} ${chunk(rest, sizes.international)}`;
    }

    return digits;
  }

  return chunk(digits, sizes.national);
}

function chunk(digits: string, sizes: number[]): string {
  const parts: string[] = [];
  let index = 0;
  let step = 0;

  while (index < digits.length) {
    const size = sizes[Math.min(step, sizes.length - 1)] ?? 3;
    parts.push(digits.slice(index, index + size));
    index += size;
    step += 1;
  }

  return parts.join(' ');
}
