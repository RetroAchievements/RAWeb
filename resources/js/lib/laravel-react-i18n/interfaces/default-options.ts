import LocaleFileInterface from './locale-file';

/**
 * The Interface that is responsible for the default options.
 */
export default interface DefaultOptionsInterface {
  fallbackLocale: string;
  locale: string;
  prevLocale: string;
  files: Record<string, unknown> | Record<string, () => Promise<unknown>>;
}
