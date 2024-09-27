/**
 * The Interface that is responsible for the Options provided.
 */
export default interface OptionsInterface {
  fallbackLocale?: string;
  locale?: string;
  files: Record<string, unknown> | Record<string, () => Promise<unknown>>;
}
