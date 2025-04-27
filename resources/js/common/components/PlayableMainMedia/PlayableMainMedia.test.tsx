import { render, screen } from '@/test';

import { PlayableMainMedia } from './PlayableMainMedia';

describe('Component: PlayableMainMedia', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given both screenshots are available, renders both images', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
      />,
    );

    // ASSERT
    const titleImage = screen.getByRole('img', { name: /title screenshot/i });
    const ingameImage = screen.getByRole('img', { name: /ingame screenshot/i });

    expect(titleImage).toBeVisible();
    expect(ingameImage).toBeVisible();
  });

  it('given both screenshots are the default "no screenshot found" image, renders nothing', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/000002.jpg"
        imageIngameUrl="https://example.com/000002.jpg"
      />,
    );

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });
});
