import { useEffect, useRef, useState } from 'react';

export function useEventAchievementSectionAnimation(options: { isInitiallyOpened: boolean }) {
  const [isOpen, setIsOpen] = useState(options.isInitiallyOpened);

  const contentRef = useRef<HTMLDivElement>(null);
  const childContainerRef = useRef<HTMLUListElement>(null);

  // Track whether this is the first render cycle.
  // We don't want to animate on first render (mount).
  const isInitialRender = useRef(true);

  // Run once on mount to properly set up the initial state.
  useEffect(() => {
    // If the section should start closed, make sure we apply proper styling.
    if (!options.isInitiallyOpened && contentRef.current) {
      contentRef.current.style.height = '0px';
      contentRef.current.style.overflow = 'hidden';
    }
  }, [options.isInitiallyOpened]);

  useEffect(() => {
    // Skip the animation on mount.
    if (isInitialRender.current) {
      isInitialRender.current = false;

      return;
    }

    const contentElement = contentRef.current;
    const childContainer = childContainerRef.current;

    if (!contentElement || !childContainer) {
      return;
    }

    // `if` is the opening animation.
    if (isOpen) {
      const height = childContainer.offsetHeight;

      // Set initial state.
      contentElement.style.height = '0px';
      contentElement.style.overflow = 'hidden';

      // Force the browser to acknowledge the initial state.
      void contentElement.offsetHeight;

      // Add the transition.
      contentElement.style.transition = 'height 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

      // Set the target height.
      contentElement.style.height = `${height}px`;

      // Remove style constraints after animation completes.
      const onTransitionEnd = () => {
        contentElement.style.height = '';
        contentElement.style.overflow = '';
        contentElement.style.transition = '';
        contentElement.removeEventListener('transitionend', onTransitionEnd);
      };

      contentElement.addEventListener('transitionend', onTransitionEnd);
    }
    // `else` is the closing animation.
    else {
      // Set the initial state with current height.
      const height = childContainer.offsetHeight;
      contentElement.style.height = `${height}px`;
      contentElement.style.overflow = 'hidden';

      // Force the browser to acknowledge the initial state.
      void contentElement.offsetHeight;

      // Add the transition.
      contentElement.style.transition = 'height 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

      // Set the target height to 0.
      contentElement.style.height = '0px';

      // Keep style constraints after animation.
      const onTransitionEnd = () => {
        contentElement.style.transition = '';
        contentElement.removeEventListener('transitionend', onTransitionEnd);
      };

      contentElement.addEventListener('transitionend', onTransitionEnd);
    }
  }, [isOpen]);

  return {
    isOpen,
    setIsOpen,
    contentRef,
    childContainerRef,
    isInitialRender,
  };
}
