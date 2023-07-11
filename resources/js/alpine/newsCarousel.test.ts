import { screen, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import Alpine from 'alpinejs';
import {
  afterEach,
  beforeAll,
  describe,
  expect,
  it,
  vi
} from 'vitest';

import { newsCarousel } from './newsCarousel';

function render() {
  (Element as any).prototype.scrollTo = vi.fn();
  (document as any).newsCarousel = newsCarousel;

  document.body.innerHTML = /** @html */ `
    <div x-data="document.newsCarousel(3)">
      <div id="news-carousel-image-list" data-testid="news-carousel-image-list" style="width: 100%; overflow-x: hidden;">
        <div style="width: 100%;">Slide 1</div>
        <div style="width: 100%;">Slide 2</div>
        <div style="width: 100%;">Slide 3</div>
      </div>

      <div id="news-carousel-indicators" style="display: flex;">
        <button @click="handleIndicatorClick(0)" class="carousel-indicator">Indicator 1</button>
        <button @click="handleIndicatorClick(1)" class="carousel-indicator">Indicator 2</button>
        <button @click="handleIndicatorClick(2)" class="carousel-indicator">Indicator 3</button>
      </div>

      <button 
            @click="handleScrollButtonClick('previous')"
            aria-label="Go to previous slide"
      ></button>
      <button 
            @click="handleScrollButtonClick('next')"
            aria-label="Go to next slide"
      ></button>

      <div data-testid="active-index-label" x-html="activeIndex">
      </div>
    </div>
  `;
}

describe('Component: newsCarousel', () => {
  beforeAll(() => {
    window.Alpine = Alpine;
    Alpine.start();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('is defined #sanity', () => {
    expect(newsCarousel).toBeDefined();
  });

  it('renders without crashing #sanity', () => {
    render();
    expect(screen.getByTestId('news-carousel-image-list')).toBeInTheDocument();
  });

  it('begins the carousel on the first slide index', async () => {
    // ARRANGE
    render();

    // ASSERT
    await waitFor(() => {
      expect(screen.getByTestId('active-index-label')).toHaveTextContent('0');
    });
  });

  it('given the user clicks the next slide button, navigates to the next slide', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /go to next/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByTestId('active-index-label')).toHaveTextContent('1');
    });
  });

  it('given the user is on the first slide and clicks the previous slide button, shows the final slide', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /go to previous/i }));

    // ASSERT
    expect(screen.getByTestId('active-index-label')).toHaveTextContent('2');
  });

  it('given the user advances past the last slide, shows the first slide', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /go to next/i }));
    await userEvent.click(screen.getByRole('button', { name: /go to next/i }));
    await userEvent.click(screen.getByRole('button', { name: /go to next/i }));

    // ASSERT
    expect(screen.getByTestId('active-index-label')).toHaveTextContent('0');
  });

  it('given the user waits for some time, auto-scrolls to the next slide', async () => {
    // ARRANGE
    vi.useFakeTimers();

    render();

    // ACT
    await vi.advanceTimersByTimeAsync(8000);

    // ASSERT
    expect(screen.getByTestId('active-index-label')).toHaveTextContent('1');
  });

  it('given the user clicks on one of the slide indicators, navigates the user to that slide', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /indicator 3/i }));

    // ASSERT
    expect(screen.getByTestId('active-index-label')).toHaveTextContent('2');
  });
});
