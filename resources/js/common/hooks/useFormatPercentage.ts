import { formatPercentage as originalFormatPercentage } from '../utils/l10n/formatPercentage';
import { usePageProps } from './usePageProps';

/**
 * A convenience wrapper around the `formatPercentage()` util. This wrapper
 * tries to automatically leverage the user's locale held in Inertia context.
 */
export function useFormatPercentage() {
  const { auth } = usePageProps();

  const locale = auth?.user.locale ?? 'en_US';

  const formatPercentage = (...args: Parameters<typeof originalFormatPercentage>) => {
    return originalFormatPercentage(args[0], { ...args[1], locale });
  };

  return { formatPercentage };
}
