import { useEffect, useRef, useState } from 'react';

export function useCanShowScrollToTopButton() {
  const [isScrollingUp, setIsScrollingUp] = useState(false);
  const lastScrollY = useRef(0);
  const isTicking = useRef(false);

  const minScrollDistance = 800;

  useEffect(() => {
    const updateScrollDirection = () => {
      const scrollY = window.scrollY;
      const documentHeight = document.documentElement.scrollHeight;
      const windowHeight = window.innerHeight;
      const maxScroll = documentHeight - windowHeight;
      const remainingScroll = maxScroll - scrollY;

      // Don't bother showing the button if we don't have much distance from its starting position.
      const isTooCloseToMinScrollDistance = remainingScroll < 200;

      // Only show the button when scrolled down at least the minimum distance.
      if (scrollY < minScrollDistance || isTooCloseToMinScrollDistance) {
        setIsScrollingUp(false);
      } else if (scrollY < lastScrollY.current) {
        setIsScrollingUp(true);
      } else if (scrollY > lastScrollY.current) {
        setIsScrollingUp(false);
      }

      lastScrollY.current = scrollY;
      isTicking.current = false;
    };

    const onScroll = () => {
      if (!isTicking.current) {
        window.requestAnimationFrame(updateScrollDirection);
        isTicking.current = true;
      }
    };

    window.addEventListener('scroll', onScroll);

    return () => {
      window.removeEventListener('scroll', onScroll);
    };
  }, [minScrollDistance]);

  return isScrollingUp;
}
