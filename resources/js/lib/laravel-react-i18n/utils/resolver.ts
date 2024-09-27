import type LocaleFileInterface from '../interfaces/locale-file';
import recognizer from './recognizer';

/**
 * Resolver the language file.
 *
 * @param files
 * @param locale
 */
export default function resolver(
  files: Record<string, any> | Record<string, () => Promise<any>>,
  locale: string,
): LocaleFileInterface[] {
  const { isJsonLocale, isPhpLocale, getJsonFile, getPhpFile } = recognizer(files);

  const jsonLocale = isJsonLocale(locale) ? files[getJsonFile(locale)] : undefined;
  const phpLocale = isPhpLocale(locale) ? files[getPhpFile(locale)] : undefined;

  const getType = (obj: string) => Object.prototype.toString.call(obj);

  if (
    ['[object Promise]', '[object Module]'].includes(getType(jsonLocale)) ||
    ['[object Promise]', '[object Module]'].includes(getType(phpLocale))
  ) {
    return [jsonLocale ? jsonLocale : { default: {} }, phpLocale ? phpLocale : { default: {} }];
  }

  if (getType(jsonLocale) === '[object Object]' || getType(phpLocale) === '[object Object]') {
    return [{ default: jsonLocale || {} }, { default: phpLocale || {} }];
  }

  if (getType(jsonLocale) === '[object Function]' || getType(phpLocale) === '[object Function]') {
    return [jsonLocale ? jsonLocale() : { default: {} }, phpLocale ? phpLocale() : { default: {} }];
  }

  return [{ default: {} }, { default: {} }];
}
