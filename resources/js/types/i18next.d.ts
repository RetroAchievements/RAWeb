/**
 * @see https://www.i18next.com/overview/typescript
 */

import 'i18next';

import type enUS from '../../../lang/en_US.json';

declare const TranslatedStringBrand: unique symbol;
export type TranslatedString = string & { [TranslatedStringBrand]: never };

export type TranslationKey = keyof typeof enUS;

declare module 'i18next' {
  interface CustomTypeOptions {
    resources: {
      translation: typeof enUS;
    };
  }

  // Extend t() to return TranslatedString.
  interface TFunction {
    <TKey extends TranslationKey>(key: TKey | TKey[], options?: object): TranslatedString;
  }
}
