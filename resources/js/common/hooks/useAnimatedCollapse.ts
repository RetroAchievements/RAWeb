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

  // Watch for dynamic content changes while the collapsible is open.
  useEffect(() => {
    if (!isOpen || !contentRef.current) {
      return;
    }

    const observer = new ResizeObserver((entries) => {
      for (const entry of entries) {
        setContentHeight(entry.target.clientHeight);
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
