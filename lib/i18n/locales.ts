export type Locale = "de" | "en";

export const DEFAULT_LOCALE: Locale = "de";
export const LOCALE_COOKIE = "dh_locale";
export const LOCALE_COOKIE_MAX_AGE = 60 * 60 * 24 * 365; // 1 year

export function parseLocale(value: string | null | undefined): Locale {
  if (value === "en" || value === "de") return value;
  return DEFAULT_LOCALE;
}

export function readLocaleCookie(): Locale {
  if (typeof document === "undefined") return DEFAULT_LOCALE;
  const match = document.cookie.match(new RegExp(`(?:^|; )${LOCALE_COOKIE}=([^;]*)`));
  return parseLocale(match ? decodeURIComponent(match[1]) : null);
}

export function writeLocaleCookie(locale: Locale): void {
  if (typeof document === "undefined") return;
  document.cookie = `${LOCALE_COOKIE}=${encodeURIComponent(locale)}; path=/; max-age=${LOCALE_COOKIE_MAX_AGE}; SameSite=Lax`;
}
