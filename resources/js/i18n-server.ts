import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

interface TranslationModule {
  default: Record<string, string>;
}

const translations: Record<string, TranslationModule> = import.meta.glob('../../lang/*.json', {
  eager: true,
});

export const createServerI18nInstance = async (locale: string) => {
  const i18nInstance = i18n.createInstance();

  const translation = translations[`../../lang/${locale}.json`];

  await i18nInstance.use(initReactI18next).init({
    lng: locale,
    fallbackLng: 'en_US',
    resources: {
      [locale]: {
        translation: translation.default,
      },
    },
    interpolation: { escapeValue: false },
  });

  return i18nInstance;
};
