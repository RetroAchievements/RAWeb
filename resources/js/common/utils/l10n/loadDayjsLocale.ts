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
}
