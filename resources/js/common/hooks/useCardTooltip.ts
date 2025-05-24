import type { ElementWithXAttributes } from 'alpinejs';
import { useEffect, useRef } from 'react';

export function useCardTooltip(args: {
  dynamicType: 'user' | 'game' | 'hub' | 'achievement' | 'ticket';
  dynamicId: string | number | null;
  dynamicContext?: string;
}) {
  const { dynamicId, dynamicType } = args;

  const elementRef = useRef<ElementWithXAttributes | null>(null);

  // TODO migrate this out of Alpine.js
  const cardTooltipProps = {
    'x-data': `tooltipComponent($el, {dynamicType: '${dynamicType}', dynamicId: '${dynamicId}', dynamicContext: '${args.dynamicContext}'})`,
    'x-on:mouseover': 'showTooltip($event)',
    'x-on:mouseleave': 'hideTooltip',
    'x-on:mousemove': 'trackMouseMovement($event)',

    ref: (el: ElementWithXAttributes | null) => {
      elementRef.current = el;
    },
  };

  /**
   * Ensure Alpine.js reinitializes tooltips on the updated DOM element after rendering.
   * If we don't do this, card tooltips can get "stuck" when their underlying avatar
   * components point to different elements during runtime.
   *
   * In other words, if an avatar is rendering for game 1, then changes client-side to
   * render for game 2, without this effect, the tooltip will still show for game 1.
   * This is because Alpine.js is not coupled to React's change detection mechanism.
   */
  useEffect(() => {
    const element = elementRef.current;

    if (element && typeof window !== 'undefined' && window.Alpine) {
      window.Alpine.destroyTree(element);

      requestAnimationFrame(() => {
        window.Alpine.initTree(element);
      });
    }

    // Cleanup.
    return () => {
      if (element && typeof window !== 'undefined' && window.Alpine) {
        // Clean up Alpine.js binding if necessary
        window.Alpine.destroyTree(element);
      }
    };
  }, [dynamicId, dynamicType]);

  return { cardTooltipProps };
}
