import { useEffect, useRef, useState } from 'react';

export function useAnimatedCollapse() {
  const [isOpen, setIsOpen] = useState(false);

  const contentRef = useRef<HTMLDivElement>(null);
  const [contentHeight, setContentHeight] = useState(0);

  useEffect(() => {
    if (contentRef.current) {
      setContentHeight(contentRef.current.offsetHeight);
    }
  }, [isOpen]);

  return {
    contentHeight,
    contentRef,
    isOpen,
    setIsOpen,
  };
}
