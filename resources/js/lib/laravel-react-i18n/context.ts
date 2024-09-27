import { createContext } from 'react';

import type ContextInterface from './interfaces/context';

export const Context = createContext<ContextInterface>({
  t: (key) => '',
  tChoice: (key) => '',
  currentLocale: () => '',
  getLocales: () => [''],
  isLocale: (locale) => true,
  loading: true,
  // eslint-disable-next-line @typescript-eslint/no-empty-function
  setLocale: (locale) => {},
});
