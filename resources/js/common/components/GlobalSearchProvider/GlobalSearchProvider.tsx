import { type FC, type ReactNode, useEffect, useState } from 'react';

import { GlobalSearch } from '../GlobalSearch';
import { GlobalSearchContext } from './GlobalSearchContext';

interface GlobalSearchProviderProps {
  children: ReactNode;
}

export const GlobalSearchProvider: FC<GlobalSearchProviderProps> = ({ children }) => {
  const [isOpen, setIsOpen] = useState(false);

  const openSearch = () => setIsOpen(true);

  // Listen for custom events to open search from non-React code.
  useEffect(() => {
    const handleOpenSearch = () => setIsOpen(true);
    if (typeof window !== 'undefined') {
      window.addEventListener('open-global-search', handleOpenSearch);
    }

    return () => window.removeEventListener('open-global-search', handleOpenSearch);
  }, []);

  return (
    <GlobalSearchContext.Provider value={{ openSearch }}>
      {children}

      <GlobalSearch isOpen={isOpen} onOpenChange={setIsOpen} />
    </GlobalSearchContext.Provider>
  );
};

// Global function to open search from anywhere (including non-React code).
if (typeof window !== 'undefined') {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- this is intentional
  (window as any).openGlobalSearch = () => {
    const event = new CustomEvent('open-global-search');
    window.dispatchEvent(event);
  };
}
