import { useContext } from 'react';

import { GlobalSearchContext } from './GlobalSearchContext';

export function useGlobalSearch() {
  const context = useContext(GlobalSearchContext);

  if (!context) {
    throw new Error('useGlobalSearch must be used within GlobalSearchProvider');
  }

  return context;
}
