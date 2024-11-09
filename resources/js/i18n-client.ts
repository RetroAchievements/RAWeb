import i18n from 'i18next';
import resourcesToBackend from 'i18next-resources-to-backend';
import { initReactI18next } from 'react-i18next';

i18n
  .use(initReactI18next)
  .use(
    resourcesToBackend((locale: string) => {
      if (!locale.includes('_')) {
        return;
      }

      return import(`../../lang/${locale}.json`);
    }),
  )
  .init({
    debug: import.meta.env.DEV,
    fallbackLng: 'en_US',
    interpolation: { escapeValue: false },
    detection: {
      order: ['htmlTag'],
    },
  });

export default i18n;
