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
      contentRef.current.style.opacity = '0';
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

    const cleanup = () => {
      if (contentElement) {
        contentElement.removeEventListener('transitionend', handleTransitionEnd);
      }
    };

    // This is a Material Design like easing curve.
    const emphasizedEasing = 'cubic-bezier(0.2, 0, 0, 1)';

    // The duration is slightly elongated from Tailwind's default (200ms).
    // This helps the animation feel a bit more "deliberate".
    const duration = 350;

    const handleTransitionEnd = (event: TransitionEvent) => {
      // Only handle the height transition completion.
      if (event.propertyName !== 'height' || event.target !== contentElement) {
        return;
      }

      if (isOpen) {
        // After the height transition completes, add a small buffer and
        // set overflow to visible to prevent content truncation. If we don't do
        // this, there will be an undesirable "pop" effect at the end of the animation.
        const currentHeight = parseFloat(contentElement.style.height);
        const newHeight = Math.ceil(currentHeight + 4); // add some px buffer

        contentElement.style.height = `${newHeight}px`;
        contentElement.style.overflow = 'visible';
      }

      cleanup();
    };

    if (isOpen) {
      // Start from zero height.
      contentElement.style.height = '0px';
      contentElement.style.overflow = 'hidden';
      contentElement.style.opacity = '0';

      // Force the browser to acknowledge this.
      void contentElement.offsetHeight;

      // Add transitions for height and opacity with coordinated timing.
      contentElement.style.transition = `
        height ${duration}ms ${emphasizedEasing}, 
        opacity ${Math.round(duration * 0.75)}ms ${emphasizedEasing}
      `;

      // Calculate our target (destination) height.
      const targetHeight = childContainer.scrollHeight;

      // Apply the opacity change immediately.
      contentElement.style.opacity = '1';

      // Apply our height change with a tiny delay for a more natural/staggered sequence.
      setTimeout(() => {
        contentElement.style.height = `${targetHeight}px`;
      }, 10);

      contentElement.addEventListener('transitionend', handleTransitionEnd);
    } else {
      // Start from the current height.
      const currentHeight = childContainer.scrollHeight;
      contentElement.style.height = `${currentHeight}px`;
      contentElement.style.overflow = 'hidden';
      contentElement.style.opacity = '1';

      // Force the browser to acknowledge this.
      void contentElement.offsetHeight;

      // Add transitions for height and opacity with coordinated timing.
      contentElement.style.transition = `
        height ${duration}ms ${emphasizedEasing}, 
        opacity ${Math.round(duration * 0.6)}ms ${emphasizedEasing}
      `;

      contentElement.style.height = '0px';
      contentElement.style.opacity = '0';

      contentElement.addEventListener('transitionend', handleTransitionEnd);
    }

    return cleanup;
  }, [isOpen]);

  return {
    isOpen,
    setIsOpen,
    contentRef,
    childContainerRef,
    isInitialRender,
  };
}
