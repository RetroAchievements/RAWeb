import { type FC, type ReactNode, useEffect, useState } from 'react';

import { GlobalSearch } from '../GlobalSearch';
import { useGlobalSearchHotkey } from '../GlobalSearch/hooks/useGlobalSearchHotkey';
import { GlobalSearchContext } from './GlobalSearchContext';

interface GlobalSearchProviderProps {
  children: ReactNode;
}

export const GlobalSearchProvider: FC<GlobalSearchProviderProps> = ({ children }) => {
  const [isOpen, setIsOpen] = useState(false);

  const openSearch = () => setIsOpen(true);

  useGlobalSearchHotkey({ onOpenChange: setIsOpen });

  // Bridge for non-React code (Blade pages, vanilla JS) that dispatches the open event.
  useEffect(() => {
    const handler = () => setIsOpen(true);
    window.addEventListener('open-global-search', handler);

    return () => window.removeEventListener('open-global-search', handler);
  }, []);

  return (
    <GlobalSearchContext.Provider value={{ openSearch }}>
      {children}

      {isOpen ? <GlobalSearch isOpen={isOpen} onOpenChange={setIsOpen} /> : null}
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
