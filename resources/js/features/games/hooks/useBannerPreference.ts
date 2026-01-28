import { useState } from 'react';
import { useCookie } from 'react-use';

import { usePageProps } from '@/common/hooks/usePageProps';

const cycleOrder: App.Platform.Enums.GameBannerPreference[] = ['normal', 'compact', 'expanded'];

export function useBannerPreference() {
  const { bannerPreference: initialValue } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const [, setCookieValue] = useCookie('banner_state');
  const [bannerPreference, setBannerPreference] =
    useState<App.Platform.Enums.GameBannerPreference>(initialValue);

  const cycleBannerPreference = () => {
    const currentIndex = cycleOrder.indexOf(bannerPreference);
    const nextIndex = (currentIndex + 1) % cycleOrder.length;
    const newValue = cycleOrder[nextIndex];

    setBannerPreference(newValue);
    setCookieValue(newValue);
  };

  return { bannerPreference, cycleBannerPreference };
}
