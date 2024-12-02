import { useEffect, useRef, useState } from 'react';
import { useScrolling } from 'react-use';

export function useActivePlayerScrollObserver() {
  const scrollRef = useRef<HTMLDivElement>(null);

  const isScrolling = useScrolling(scrollRef);

  const [hasScrolled, setHasScrolled] = useState(false);

  useEffect(() => {
    if (!hasScrolled && isScrolling) {
      setHasScrolled(true);
    }
  }, [hasScrolled, isScrolling]);

  return { scrollRef, hasScrolled };
}
