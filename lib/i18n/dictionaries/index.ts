import type { Locale } from "../locales";
import { de } from "./de";
import { en, type Dictionary } from "./en";

const dictionaries: Record<Locale, Dictionary> = { de, en };

export function getDictionary(locale: Locale): Dictionary {
  return dictionaries[locale];
}

export type { Dictionary };
