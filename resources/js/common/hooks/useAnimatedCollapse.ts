import { useEffect, useRef, useState } from 'react';

export function useAnimatedCollapse() {
  const [isOpen, setIsOpen] = useState(false);

  const contentRef = useRef<HTMLDivElement>(null);
  const [contentHeight, setContentHeight] = useState(0);

  // Recalculate height when the collapsible is opened.
  useEffect(() => {
    if (contentRef.current) {
      setContentHeight(contentRef.current.offsetHeight);
    }
  }, [isOpen]);

  // Observe content size changes so the height stays in sync
  // when children are dynamically added or removed (eg: after claiming a game).
  useEffect(() => {
    if (!isOpen || !contentRef.current) {
      return;
    }

    const observer = new ResizeObserver(([entry]) => {
      if (entry) {
        setContentHeight(entry.target.scrollHeight);
      }
    });

    observer.observe(contentRef.current);

    return () => observer.disconnect();
  }, [isOpen]);

  return {
    contentHeight,
    contentRef,
    isOpen,
    setIsOpen,
  };
}
