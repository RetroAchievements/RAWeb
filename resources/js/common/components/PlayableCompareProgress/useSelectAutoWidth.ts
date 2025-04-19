import { useEffect, useRef, useState } from 'react';

export function useSelectAutoWidth() {
  const [autoWidth, setAutoWidth] = useState(0);

  const autoWidthContainerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const updateWidth = () => {
      if (autoWidthContainerRef.current) {
        setAutoWidth(autoWidthContainerRef.current.offsetWidth);
      }
    };

    updateWidth();
    window.addEventListener('resize', updateWidth);

    return () => {
      window.removeEventListener('resize', updateWidth);
    };
  }, []);

  return { autoWidth, autoWidthContainerRef };
}
