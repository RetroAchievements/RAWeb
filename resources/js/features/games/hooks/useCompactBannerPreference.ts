import { useState } from 'react';
import { useCookie } from 'react-use';

import { usePageProps } from '@/common/hooks/usePageProps';

export function useCompactBannerPreference() {
  const { prefersCompactBanners: initialValue } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const [, setCookieValue] = useCookie('prefers_compact_game_banners');
  const [prefersCompactBanners, setPrefersCompactBanners] = useState(initialValue);

  const toggleCompactBanners = () => {
    const newValue = !prefersCompactBanners;

    setPrefersCompactBanners(newValue);
    setCookieValue(newValue ? '1' : '0');
  };

  return { prefersCompactBanners, toggleCompactBanners };
}
