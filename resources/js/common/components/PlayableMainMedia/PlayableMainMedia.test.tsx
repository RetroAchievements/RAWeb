import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createGameScreenshot } from '@/test/factories';

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

  it('given the system has analog TV output and is not pixelated, applies a 4:3 aspect ratio to both images', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        expectedWidth={256}
        expectedHeight={224}
        hasAnalogTvOutput={true}
        isPixelated={false}
      />,
    );

    // ASSERT
    const titleImage = screen.getByRole('img', { name: /title screenshot/i });
    const ingameImage = screen.getByRole('img', { name: /ingame screenshot/i });

    expect(titleImage).toHaveStyle({ aspectRatio: `${4 / 3}` });
    expect(ingameImage).toHaveStyle({ aspectRatio: `${4 / 3}` });
  });

  it('given the system has analog TV output and is pixelated, does not apply a 4:3 aspect ratio', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        expectedWidth={256}
        expectedHeight={224}
        hasAnalogTvOutput={true}
        isPixelated={true}
      />,
    );

    // ASSERT
    const titleImage = screen.getByRole('img', { name: /title screenshot/i });
    const ingameImage = screen.getByRole('img', { name: /ingame screenshot/i });

    expect(titleImage).not.toHaveStyle({ aspectRatio: `${4 / 3}` });
    expect(ingameImage).not.toHaveStyle({ aspectRatio: `${4 / 3}` });
    expect(titleImage).toHaveStyle({ imageRendering: 'pixelated' });
    expect(ingameImage).toHaveStyle({ imageRendering: 'pixelated' });
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

  it('given numScreenshots is greater than zero, renders images as clickable buttons instead of zoomable images', () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'title' }),
      createGameScreenshot({ id: 2, type: 'ingame' }),
    ];

    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={2}
        screenshots={screenshots}
      />,
    );

    // ASSERT
    const buttons = screen.getAllByRole('button');
    expect(buttons).toHaveLength(2);
  });

  it('given numScreenshots is greater than one, shows a count', () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'title' }),
      createGameScreenshot({ id: 2, type: 'ingame' }),
      createGameScreenshot({ id: 3, type: 'completion' }),
    ];

    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={3}
        screenshots={screenshots}
      />,
    );

    // ASSERT
    expect(screen.getByText('3')).toBeVisible();
  });

  it('given numScreenshots is exactly one, does not show a count', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={1}
        screenshots={[createGameScreenshot({ id: 1, type: 'ingame' })]}
      />,
    );

    // ASSERT
    expect(screen.queryByText('1')).not.toBeInTheDocument();
  });

  it('given numScreenshots is greater than zero but screenshots have not loaded yet, disables the buttons', () => {
    // ARRANGE
    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={2}
        screenshots={undefined}
      />,
    );

    // ASSERT
    const buttons = screen.getAllByRole('button');
    expect(buttons[0]).toBeDisabled();
    expect(buttons[1]).toBeDisabled();
  });

  it('given screenshots do not include a matching type for the clicked image, still opens the gallery without crashing', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'completion' })];

    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={1}
        screenshots={screenshots}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('img', { name: /title screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });
  });

  it('given the user clicks the title image button, opens the screenshot gallery dialog', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'title' }),
      createGameScreenshot({ id: 2, type: 'ingame' }),
    ];

    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={2}
        screenshots={screenshots}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('img', { name: /title screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });
  });

  it('given the user clicks the ingame image button, opens the screenshot gallery dialog', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'title' }),
      createGameScreenshot({ id: 2, type: 'ingame' }),
    ];

    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={2}
        screenshots={screenshots}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('img', { name: /ingame screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });
  });

  it('given the gallery dialog is open, closing it resets the dialog state', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'title' }),
      createGameScreenshot({ id: 2, type: 'ingame' }),
    ];

    render(
      <PlayableMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
        numScreenshots={2}
        screenshots={screenshots}
      />,
    );

    await userEvent.click(screen.getByRole('img', { name: /title screenshot/i }));
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /close/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });
});
