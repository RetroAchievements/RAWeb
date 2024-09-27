import type OptionsInterface from './options';

/**
 * The Interface that is responsible for the OptionsProvider provided.
 */
export default interface OptionsProviderInterface extends OptionsInterface {
  prevLocale?: string;
}
