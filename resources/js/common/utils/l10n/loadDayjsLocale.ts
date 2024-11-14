import dayjs from 'dayjs';

/**
 * Dynamically load the Day.js locale code based on the user's
 * locale preference. This method of dynamic loading helps keep
 * the client-side JS bundle size down.
 */
export async function loadDayjsLocale(userLocale: string) {
  if (userLocale === 'pt_BR') {
    try {
      await import('dayjs/locale/pt-br.js');
      dayjs.locale('pt-br');
    } catch (err) {
      console.warn('Unable to load Day.js locale for pt_BR.', err);
    }
  }

  if (userLocale === 'pl_PL') {
    try {
      await import('dayjs/locale/pl.js');
      dayjs.locale('pl');
    } catch (err) {
      console.warn('Unable to load Day.js locale for pl_PL.', err);
    }
  }

  if (userLocale === 'es_ES') {
    try {
      await import('dayjs/locale/es.js');
      dayjs.locale('es');
    } catch (err) {
      console.warn('Unable to load Day.js locale for es_ES.', err);
    }
  }
}
