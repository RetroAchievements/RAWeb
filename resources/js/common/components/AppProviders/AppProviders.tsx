import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import type { FC, ReactNode } from 'react';

interface AppProvidersProps {
  children: ReactNode;
}

export const AppProviders: FC<AppProvidersProps> = ({ children }) => {
  return (
    <LaravelReactI18nProvider
      locale="en_US"
      fallbackLocale="en_US"
      files={import.meta.glob('/lang/*.json')}
    >
      {children}
    </LaravelReactI18nProvider>
  );
};
