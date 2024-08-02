/**
 * If the user does not perform any interactions with the carousel,
 * the next slide will show after seven seconds.
 */
const AUTO_SCROLL_DELAY = 7000; // Seven seconds

/**
 * When a wrap occurs, such as navigating from the last slide to
 * the first slide, this is how long it takes before the carousel
 * to "wrap" the list of slides.
 */
const RESET_DELAY = 200;

/** These may be consumed by the AlpineJS component, but they are hidden from the outside world. */
const privateUtils = {
  getImageListElement: (): HTMLDivElement | null =>
    document.querySelector<HTMLDivElement>('#news-carousel-image-list'),
  getIndicatorsCount: () => document.querySelectorAll('.carousel-indicator').length,

  /**
   * Scroll to somewhere in the carousel image list, with position as
   * a literal left position in terms of pixels on the horizontal scroll bar.
   */
  scrollToPosition: (
    imageListEl: HTMLDivElement,
    position: number,
    scrollBehavior: 'smooth' | 'instant' = 'smooth',
  ) => {
    imageListEl.scrollTo({
      left: position,
      behavior: scrollBehavior as ScrollBehavior,
    });
  },

  /**
   * Scroll to somewhere in the carousel image list, with index as
   * the slide index to scroll to.
   */
  scrollToIndex: (
    imageListEl: HTMLDivElement,
    index: number,
    scrollBehavior: 'smooth' | 'instant' = 'smooth',
  ) => {
    const offsetWidth = imageListEl.offsetWidth;
    const position = index * offsetWidth;

    return privateUtils.scrollToPosition(imageListEl, position, scrollBehavior);
  },

  updateTextElementsOpacity: (textEls: NodeListOf<Element>, isActive: boolean) => {
    setTimeout(() => {
      for (const textEl of textEls) {
        if (isActive) {
          textEl.classList.add('lg:!opacity-100');
        } else {
          textEl.classList.remove('lg:!opacity-100');
        }
      }
    }, RESET_DELAY);
  },
};

const newsCarouselStore = {
  activeIndex: 0,
  autoScrollInterval: undefined as string | number | NodeJS.Timeout | undefined,
  totalSlideCount: 10,

  /**
   * When this function is called, the slides will begin autoscrolling
   * at a defined constant interval.
   */
  startAutoScroll() {
    this.autoScrollInterval = setInterval(() => {
      this.carouselScroll('next');
    }, AUTO_SCROLL_DELAY);
  },

  /** Stop autoscrolling the carousel slides. */
  pause() {
    clearInterval(this.autoScrollInterval);
  },

  /**
   * Begin autoscrolling the carousel slides. Also, to be safe,
   * clear any existing interval in memory so we don't have
   * accidental double navigations.
   */
  resume() {
    clearInterval(this.autoScrollInterval);
    this.startAutoScroll();
  },

  /**
   * It is very likely that a window resize event will change the
   * width of the carousel's horizontal scrollbar. To mitigate the
   * risk of some nasty bugs that happen from this, instantly navigate
   * to the very first slide on a resize event.
   */
  handleWindowResize() {
    this.resetScrollPosition(0);
    this.updateActiveSlideTextVisibility();
  },

  /**
   * Scroll the carousel one position in the specified direction.
   * @param direction The direction to scroll.
   */
  carouselScroll(direction: 'previous' | 'next') {
    const imageListEl = privateUtils.getImageListElement();

    if (!imageListEl) {
      return null;
    }

    const isAtEnd = this.activeIndex === this.totalSlideCount - 1;
    const isAtBeginning = this.activeIndex === 0;

    if (direction === 'next' && isAtEnd) {
      this.resetCarouselToIndex(imageListEl, 0);
    } else if (direction === 'previous' && isAtBeginning) {
      this.resetCarouselToIndex(imageListEl, this.totalSlideCount - 1);
    } else {
      this.activeIndex = direction === 'next' ? this.activeIndex + 1 : this.activeIndex - 1;
      privateUtils.scrollToIndex(imageListEl, this.activeIndex);
    }

    this.updateActiveSlideTextVisibility();
    return null;
  },

  /**
   * Reset the carousel to the specified slide index.
   * This generally occurs when the user is performing some kind
   * of navigation that causes a slide wrap, eg: pressing the
   * next button while viewing the last slide.
   */
  resetCarouselToIndex(imageListEl: HTMLDivElement, newIndex: number) {
    imageListEl.classList.add('opacity-0');

    setTimeout(() => {
      privateUtils.scrollToIndex(imageListEl, newIndex, 'instant');
      imageListEl.classList.remove('opacity-0');
    }, RESET_DELAY);

    this.activeIndex = newIndex;
  },

  /**
   * Instantly force the carousel to a specific slide.
   */
  resetScrollPosition(position = 0) {
    const imageListEl = privateUtils.getImageListElement();
    if (imageListEl) {
      privateUtils.scrollToPosition(imageListEl, position, 'instant');
      this.activeIndex = position === 0 ? 0 : privateUtils.getIndicatorsCount() - 1;
    }
  },

  /**
   * Animate in the text of the visible slide. Does not apply to mobile devices.
   */
  updateActiveSlideTextVisibility() {
    const allSlideEls = document.querySelectorAll('#news-carousel-image-list > div');
    for (const [index, slideEl] of allSlideEls.entries()) {
      const allTextEls = slideEl.querySelectorAll('.transition.duration-300');
      const isIndexActive = index === this.activeIndex;

      privateUtils.updateTextElementsOpacity(allTextEls, isIndexActive);
    }
  },

  updateActiveIndex() {
    setTimeout(() => {
      const imageListEl = document.querySelector<HTMLDivElement>('#news-carousel-image-list');
      if (imageListEl) {
        const carouselItemWidth = imageListEl.offsetWidth;
        const scrollLeft = imageListEl.scrollLeft ?? 0;

        this.activeIndex = Math.round(scrollLeft / carouselItemWidth);
        this.updateActiveSlideTextVisibility();
      }
    }, 200);
  },

  /**
   * Manually navigate one slide in a given direction.
   * @param direction The direction to navigate to.
   */
  handleScrollButtonClick(direction: 'previous' | 'next') {
    this.pause();
    this.carouselScroll(direction);
    this.resume();
  },

  /**
   * Manually navigate to a given slide index.
   * @param index The slide index to navigate to.
   */
  handleIndicatorClick(index: number) {
    const imageListEl = privateUtils.getImageListElement();
    if (imageListEl) {
      this.pause();

      // We don't want the user to see us rapidly scrolling through
      // lots of images. That can be disorienting. If they're moving
      // to a position far away, just animate them there.
      const absoluteDistance = Math.abs(index - this.activeIndex);

      this.activeIndex = index;

      if (absoluteDistance > 2) {
        this.resetCarouselToIndex(imageListEl, index);
      } else {
        privateUtils.scrollToIndex(imageListEl, index);
      }

      this.resume();
      this.updateActiveSlideTextVisibility();
    }
  },

  /** Initialize the carousel. */
  init() {
    setTimeout(() => {
      this.startAutoScroll();
    }, AUTO_SCROLL_DELAY); // Give the first item more emphasis.

    // Listen for window resize events. If we don't, we'll lose our tracking
    // position and nasty bugs can emerge on mobile devices.
    window.addEventListener('resize', () => {
      this.handleWindowResize();
    });

    // This prevents an autoscroll from instantly happening if the user changes
    // their tab, then returns to this tab. The timer should be reset when this
    // interaction occurs to prevent a jarring carousel item transition experience.
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        this.resume();
      } else {
        this.pause();
      }
    });
  },

  /** Clean up event listeners when destroying the carousel to prevent memory leaks. */
  destroy() {
    if (process.env?.['MODE'] === 'test') {
      return;
    }

    window.removeEventListener('resize', this.handleWindowResize);
    document.removeEventListener('visibilitychange', this.handleWindowResize);
  },
};

export function newsCarouselComponent(totalSlideCount = 10) {
  return { ...newsCarouselStore, totalSlideCount };
}
