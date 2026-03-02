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

  it('given expected dimensions, sets width and height on both images', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        expectedWidth={256}
        expectedHeight={224}
      />,
    );

    // ASSERT
    const titleImage = screen.getByRole('img', { name: /title screenshot/i });
    const ingameImage = screen.getByRole('img', { name: /ingame screenshot/i });

    expect(titleImage).toHaveAttribute('width', '256');
    expect(titleImage).toHaveAttribute('height', '224');
    expect(ingameImage).toHaveAttribute('width', '256');
    expect(ingameImage).toHaveAttribute('height', '224');
  });

  it('given no expected dimensions, does not set width and height on images', () => {
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

    expect(titleImage).not.toHaveAttribute('width');
    expect(titleImage).not.toHaveAttribute('height');
    expect(ingameImage).not.toHaveAttribute('width');
    expect(ingameImage).not.toHaveAttribute('height');
  });

  it('given the system has analog TV output and known resolutions, applies a 4:3 aspect ratio to both images', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        expectedWidth={256}
        expectedHeight={224}
        hasAnalogTvOutput={true}
      />,
    );

    // ASSERT
    const titleImage = screen.getByRole('img', { name: /title screenshot/i });
    const ingameImage = screen.getByRole('img', { name: /ingame screenshot/i });

    expect(titleImage).toHaveStyle({ aspectRatio: `${4 / 3}` });
    expect(ingameImage).toHaveStyle({ aspectRatio: `${4 / 3}` });
  });

  it('given the system has analog TV output but no known resolutions, still applies a 4:3 aspect ratio', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        hasAnalogTvOutput={true}
      />,
    );

    // ASSERT
    const titleImage = screen.getByRole('img', { name: /title screenshot/i });
    const ingameImage = screen.getByRole('img', { name: /ingame screenshot/i });

    expect(titleImage).toHaveStyle({ aspectRatio: `${4 / 3}` });
    expect(ingameImage).toHaveStyle({ aspectRatio: `${4 / 3}` });
  });

  it('given the system does not have analog TV output, does not apply a 4:3 aspect ratio', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        expectedWidth={256}
        expectedHeight={224}
        hasAnalogTvOutput={false}
      />,
    );

    // ASSERT
    const titleImage = screen.getByRole('img', { name: /title screenshot/i });
    const ingameImage = screen.getByRole('img', { name: /ingame screenshot/i });

    expect(titleImage).not.toHaveStyle({ aspectRatio: `${4 / 3}` });
    expect(ingameImage).not.toHaveStyle({ aspectRatio: `${4 / 3}` });
  });
});
