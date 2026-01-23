import { useTranslation } from 'react-i18next';

import { formatGameReleasedAt as originalFormatGameReleasedAt } from '../utils/formatGameReleasedAt';

/**
 * A convenience wrapper around the `formatGameReleasedAt()` util. This wrapper
 * automatically leverages the user's locale from i18n context, preventing
 * SSR hydration mismatches from locale leaking between concurrent requests.
 */
export function useFormatGameReleasedAt() {
  const { i18n } = useTranslation();

  const formatGameReleasedAt = (
    releasedAt: App.Platform.Data.Game['releasedAt'],
    releasedAtGranularity: App.Platform.Data.Game['releasedAtGranularity'],
  ): string | null => {
    return originalFormatGameReleasedAt(releasedAt, releasedAtGranularity, i18n.language);
  };

  return { formatGameReleasedAt };
}
