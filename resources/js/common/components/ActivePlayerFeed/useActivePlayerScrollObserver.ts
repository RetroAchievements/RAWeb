import { useEffect, useRef, useState } from 'react';

export function useActivePlayerScrollObserver() {
  const scrollRef = useRef<HTMLElement>(null);
  const [hasScrolled, setHasScrolled] = useState(false);

  useEffect(() => {
    const element = scrollRef.current;
    if (!element) {
      return;
    }

    const handleScroll = () => {
      setHasScrolled(true);
    };

    element.addEventListener('scroll', handleScroll, { once: true });

    return () => {
      element.removeEventListener('scroll', handleScroll);
    };
  }, []);

  return { scrollRef, hasScrolled };
}
