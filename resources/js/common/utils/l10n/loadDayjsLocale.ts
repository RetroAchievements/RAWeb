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

      case 'ca_ES':
        await import('dayjs/locale/ca.js');
        dayjs.locale('ca');
        break;

      case 'cy_GB':
        await import('dayjs/locale/cy.js');
        dayjs.locale('cy');
        break;

      case 'de_DE':
        await import('dayjs/locale/de.js');
        dayjs.locale('de');
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

      case 'it_IT':
        await import('dayjs/locale/it.js');
        dayjs.locale('it');
        break;

      case 'nl_BE':
        await import('dayjs/locale/nl-be.js');
        dayjs.locale('nl-be');
        break;

      case 'ru_RU':
        await import('dayjs/locale/ru.js');
        dayjs.locale('ru');
        break;

      case 'sv_SE':
        await import('dayjs/locale/sv.js');
        dayjs.locale('sv');
        break;

      case 'pl_PL':
        await import('dayjs/locale/pl.js');
        dayjs.locale('pl');
        break;

      case 'pt_BR':
        await import('dayjs/locale/pt-br.js');
        dayjs.locale('pt-br');
        break;

      case 'vi_VN':
        await import('dayjs/locale/vi.js');
        dayjs.locale('vi');
        break;

      default:
        console.warn(`Locale ${userLocale} is not supported.`);
    }
  } catch (err) {
    console.warn(`Unable to load Day.js locale for ${userLocale}.`, err);
  }
}
