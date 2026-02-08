import { useTranslation } from 'react-i18next';

import { formatNumber as originalFormatNumber } from '../utils/l10n/formatNumber';

/**
 * A convenience wrapper around the `formatNumber()` util. This wrapper
 * automatically leverages the user's locale from i18n context, preventing
 * SSR hydration mismatches from locale leaking between concurrent requests.
 */
export function useFormatNumber() {
  const { i18n } = useTranslation();

  const formatNumber = (number = 0) => {
    return originalFormatNumber(number, { locale: i18n.language });
  };

  return { formatNumber };
}
