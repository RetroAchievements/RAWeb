import type { RefObject } from 'react';
import { useEffect, useRef, useState } from 'react';
import { useScrolling } from 'react-use';

export function useActivePlayerScrollObserver() {
  const scrollRef = useRef<HTMLElement>(null);

  const isScrolling = useScrolling(scrollRef as RefObject<HTMLElement>);

  const [hasScrolled, setHasScrolled] = useState(false);

  useEffect(() => {
    if (!hasScrolled && isScrolling) {
      setHasScrolled(true);
    }
  }, [hasScrolled, isScrolling]);

  return { scrollRef, hasScrolled };
}
