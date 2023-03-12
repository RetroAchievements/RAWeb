import { UAParser } from 'ua-parser-js';
import {
  computePosition,
  offset,
  shift,
  flip,
  Middleware
} from '@floating-ui/dom';

type EventListener = (event: Event) => void;

let currentTooltipId: number | null = null;
let tooltipEl: HTMLElement | null = null;

const cursorShiftMiddleware = (currentX?: number, currentY?: number): Middleware => ({
  name: 'cursorShift',
  async fn(state) {
    const shiftResult = await shift().fn({
      ...state,
      x: currentX ?? state.x,
      y: currentY ?? state.y,
    });

    return {
      data: {
        x: shiftResult.x,
        y: shiftResult.y
      }
    };
  }
});

const cursorFlipMiddleware = (): Middleware => ({
  name: 'cursorFlip',
  async fn(state) {
    const flipResult = await flip().fn({
      ...state,
    });

    return {
      data: {
        isFlipped: !!flipResult?.data
      }
    };
  }
});

function update(anchorEl: HTMLElement, tooltipEl: HTMLElement, givenX?: number, givenY?: number) {
  computePosition(anchorEl, tooltipEl, {
    placement: 'bottom-end',
    middleware: [offset(6), cursorFlipMiddleware(), cursorShiftMiddleware(givenX, givenY)],
  }).then(async ({ x, y, middlewareData }) => {
    const setX = middlewareData.cursorShift.x ?? givenX ?? x;
    let setY = givenY ?? y;

    if (middlewareData.cursorFlip.isFlipped) {
      const tooltipHeight = tooltipEl.getBoundingClientRect().height;
      setY -= (tooltipHeight - 12);
    }

    Object.assign(tooltipEl.style, {
      left: `${setX}px`,
      top: `${setY}px`
    });
  });
}

const showTooltip = (anchorEl: HTMLElement, html: string) => {
  if (tooltipEl !== null) {
    tooltipEl.remove();
    tooltipEl = null;
  }

  currentTooltipId = Math.random();
  tooltipEl = document.createElement('div');

  tooltipEl.classList.add('animate-fade-in', 'drop-shadow-2xl', 'border', 'border-embed-highlight', 'hidden', 'w-max', 'absolute', 'top-0', 'left-0', 'rounded');
  tooltipEl.innerHTML = html;
  document.body.appendChild(tooltipEl);

  tooltipEl.style.display = 'block';

  update(anchorEl, tooltipEl);
};

const hideTooltip = (tooltipIdToHide: number | null) => {
  if (tooltipEl) {
    tooltipEl.style.transition = 'opacity 200ms ease, transform 200ms ease';
    tooltipEl.style.opacity = '0';
    tooltipEl.style.transform = 'scale(0.95)';

    setTimeout(() => {
      if (tooltipEl && currentTooltipId === tooltipIdToHide) {
        tooltipEl.style.display = '';

        tooltipEl.style.removeProperty('transition');
        tooltipEl.style.removeProperty('transform');
        tooltipEl.style.removeProperty('opacity');
      }
    }, 200);
  }
};

const handleMouseMove = (anchorEl: HTMLElement, event: MouseEvent) => {
  if (tooltipEl) {
    update(anchorEl, tooltipEl, event.pageX + 12, event.pageY + 6);
  }
};

function attachTooltip(
  anchorEl: HTMLElement,
  options: Partial<{
    staticHtmlContent: string,
    dynamicType: string,
    dynamicId: string,
    dynamicContext: unknown
  }>
) {
  const staticHtmlTooltipListeners = [
    ['mouseover', () => showTooltip(anchorEl, options?.staticHtmlContent ?? '')],
    ['mouseleave', () => hideTooltip(currentTooltipId)],
    ['mousemove', (event: MouseEvent) => handleMouseMove(anchorEl, event)],
    ['focus', showTooltip],
    ['blur', hideTooltip]
  ];

  staticHtmlTooltipListeners.forEach(([event, listenerFn]) => {
    anchorEl.addEventListener(event as keyof HTMLElementEventMap, listenerFn as EventListener);
  });
}

let wasMobileIosDetected: boolean | null = null;

function detectMobileIos() {
  if ('userAgent' in navigator) {
    const { getOS, getDevice } = new UAParser(navigator.userAgent);

    const { name: osName } = getOS();
    const { type: deviceType } = getDevice();

    if (osName === 'iOS' && deviceType === 'mobile') {
      wasMobileIosDetected = true;
    } else {
      wasMobileIosDetected = false;
    }
  }
}

function handleMouseOver(tipDomContent: string) {
  /**
   * wz_tooltip.js causes issues with touch events on
   * iOS devices. Android devices are okay.
   * @see https://github.com/RetroAchievements/RAWeb/issues/1365
   */
  if (wasMobileIosDetected === null) {
    detectMobileIos();
  }

  if (wasMobileIosDetected === false && window.Tip) {
    window.Tip(tipDomContent);
  }
}

export const mobileSafeTipEvents = {
  attachTooltip,
  mouseOver: handleMouseOver,
};
