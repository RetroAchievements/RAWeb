/**
 * JavaScript's built-in date localization uses different locale
 * codes than Dayjs does. We need to be able to map from
 * Dayjs to native.
 *
 * "pt-br" --> "pt-BR"
 */
export function mapDayjsLocaleToIntlLocale(locale: string) {
  if (locale.includes('-')) {
    const [language, region] = locale.split('-');

    return `${language.toLowerCase()}-${region.toUpperCase()}`;
  }

  return locale.toLowerCase();
}
