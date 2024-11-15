import dayjs from 'dayjs';

/**
 * Dynamically load the Day.js locale code based on the user's
 * locale preference. This method of dynamic loading helps keep
 * the client-side JS bundle size down.
 */
export async function loadDayjsLocale(userLocale: string) {
  try {
    switch (userLocale) {
      case 'en':
      case 'en_US':
        break;

      case 'en_GB':
        await import('dayjs/locale/en-gb.js');
        dayjs.locale('en-gb');
        break;

      case 'es_ES':
        await import('dayjs/locale/es.js');
        dayjs.locale('es');
        break;

      case 'fr_FR':
        await import('dayjs/locale/fr.js');
        dayjs.locale('fr');
        break;

      case 'pl_PL':
        await import('dayjs/locale/pl.js');
        dayjs.locale('pl');
        break;

      case 'pt_BR':
        await import('dayjs/locale/pt-br.js');
        dayjs.locale('pt-br');
        break;

      default:
        console.warn(`Locale ${userLocale} is not supported.`);
    }
  } catch (err) {
    console.warn(`Unable to load Day.js locale for ${userLocale}.`, err);
  }
}
