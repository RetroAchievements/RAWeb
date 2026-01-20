import i18n from 'i18next';
import resourcesToBackend from 'i18next-resources-to-backend';

export const createServerI18nInstance = async (locale: string) => {
  const i18nInstance = i18n.createInstance();

  await i18nInstance
    .use(
      resourcesToBackend((requestedLocale: string) => {
        if (!requestedLocale.includes('_')) {
          return;
        }

        return import(`../../lang/${requestedLocale}.json`);
      }),
    )
    .init({
      lng: locale,
      fallbackLng: 'en_US',
      interpolation: { escapeValue: false },
    });

  return i18nInstance;
};
