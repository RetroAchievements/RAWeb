import type ReplacementsInterface from './replacements';

/**
 *
 */
export default interface ContextInterface<T extends string = any> {
  currentLocale: () => string;
  getLocales: () => string[];
  isLocale: (locale: string) => boolean;
  loading: boolean;
  setLocale: (locale: string) => void;
  t: (key: T, replacements?: ReplacementsInterface) => string;
  tChoice: (key: T, number: number, replacements?: ReplacementsInterface) => string;
}
