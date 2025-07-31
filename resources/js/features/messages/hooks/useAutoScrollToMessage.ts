import { useEffect } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

export function useAutoScrollToMessage() {
  const { ziggy } = usePageProps();

  useEffect(() => {
    const messageId = ziggy.query.message;
    if (messageId) {
      setTimeout(() => {
        const element = document.getElementById(String(messageId));
        element?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
  }, [ziggy.query.message]);
}
