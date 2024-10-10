import { formatNumber as originalFormatNumber } from '../utils/l10n/formatNumber';
import { usePageProps } from './usePageProps';

/**
 * A convenience wrapper around the `formatNumber()` util. This wrapper tries
 * to automatically leverage the user's locale held in Inertia context.
 */
export function useFormatNumber() {
  const { auth } = usePageProps();

  const locale = auth?.user.locale ?? 'en_US';

  const formatNumber = (number: number) => {
    return originalFormatNumber(number, { locale });
  };

  return { formatNumber };
}
