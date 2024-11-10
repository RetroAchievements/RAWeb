import dayjs from 'dayjs';

const LOCALE_MAP: Record<string, string> = {
  pt_BR: 'pt-br',
  pl_PL: 'pl',
  es_ES: 'es',
};

/**
 * Dynamically load the Day.js locale code based on the user's
 * locale preference. This method of dynamic loading helps keep
 * the client-side JS bundle size down.
 */
export async function loadDayjsLocale(userLocale: string) {
  const dayjsLocale = LOCALE_MAP[userLocale];

  if (!dayjsLocale) {
    return;
  }

  try {
    await import(`dayjs/locale/${dayjsLocale}.js`);
    dayjs.locale(dayjsLocale);
  } catch (err) {
    console.warn(`Unable to load Day.js locale for ${userLocale}.`, err);
  }
}
