import { render, screen } from '@/test';

import { PlayableMobileMediaCarousel } from './PlayableMobileMediaCarousel';

describe('Component: PlayableMobileMediaCarousel', () => {
  const defaultProps = {
    imageTitleUrl: 'https://example.com/title.jpg',
    imageIngameUrl: 'https://example.com/ingame.jpg',
  };

  beforeEach(() => {
    const mockIntersectionObserver = vi.fn();
    mockIntersectionObserver.mockReturnValue({
      observe: () => null,
      unobserve: () => null,
      disconnect: () => null,
    });
    window.IntersectionObserver = mockIntersectionObserver;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayableMobileMediaCarousel {...defaultProps} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given image URLs, renders both images in the carousel', () => {
    // ARRANGE
    render(<PlayableMobileMediaCarousel {...defaultProps} />);

    // ASSERT
    const images = screen.getAllByRole('img');
    expect(images).toHaveLength(2);
    expect(images[0]).toHaveAttribute('src', defaultProps.imageTitleUrl);
    expect(images[1]).toHaveAttribute('src', defaultProps.imageIngameUrl);
  });
});
