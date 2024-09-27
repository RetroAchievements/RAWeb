/**
 *
 * @param files
 */
export default function recognizer(
  files: Record<string, unknown> | Record<string, () => Promise<unknown>>,
) {
  const jsonLocales: string[] = [];
  const phpLocales: string[] = [];
  const jsonFileLocales: Record<string, string> = {};
  const phpFileLocales: Record<string, string> = {};

  Object.keys(files).map((file) => {
    const match = file.match(/(.*)\/(.*).json$/) || [];

    if (match?.[0] && match?.[2]) {
      if (match[2].match(/php_/)) {
        const locale = match[2].replace('php_', '');
        phpLocales.push(locale);
        phpLocales.sort();
        phpFileLocales[locale] = match[0];
      } else {
        const locale = match[2];
        jsonLocales.push(locale);
        jsonLocales.sort();
        jsonFileLocales[locale] = match[0];
      }
    }
  });

  const locales = [...jsonLocales, ...phpLocales]
    .filter((locale, index, array) => array.indexOf(locale) === index)
    .sort();

  return {
    /**
     *
     * @param locale
     */
    isLocale: (locale: string): boolean => locales.includes(locale),
    /**
     *
     */
    getLocales: () => locales,
    /**
     *
     * @param locale
     */
    isJsonLocale: (locale: string): boolean => jsonLocales.includes(locale),
    /**
     *
     */
    getJsonLocales: () => jsonLocales,
    /**
     *
     * @param locale
     */
    isPhpLocale: (locale: string): boolean => phpLocales.includes(locale),
    /**
     *
     */
    getPhpLocales: () => phpLocales,
    /**
     *
     */
    getJsonFile: (locale: string): string => jsonFileLocales?.[locale],
    /**
     *
     */
    getPhpFile: (locale: string): string => phpFileLocales?.[locale],
  };
}
