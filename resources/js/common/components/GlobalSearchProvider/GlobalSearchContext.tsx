import { createContext } from 'react';

interface GlobalSearchContextType {
  openSearch: () => void;
}

export const GlobalSearchContext = createContext<GlobalSearchContextType | undefined>(undefined);
