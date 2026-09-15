import { type UIEvent, useRef } from 'react';

export function useSyncedHorizontalScroll() {
  const headerElRef = useRef<HTMLDivElement>(null);
  const bodyElRef = useRef<HTMLDivElement>(null);

  const handleHeaderScroll = (event: UIEvent<HTMLDivElement>) => {
    if (bodyElRef.current) {
      bodyElRef.current.scrollLeft = event.currentTarget.scrollLeft;
    }
  };

  const handleBodyScroll = (event: UIEvent<HTMLDivElement>) => {
    if (headerElRef.current) {
      headerElRef.current.scrollLeft = event.currentTarget.scrollLeft;
    }
  };

  return { bodyElRef, handleBodyScroll, handleHeaderScroll, headerElRef };
}
