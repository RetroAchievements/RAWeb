import type { ReactNode } from 'react';

import type OptionsInterface from './options';

/**
 *
 */
export default interface I18nProviderProps extends OptionsInterface {
  children: ReactNode;
  ssr?: boolean;
}
