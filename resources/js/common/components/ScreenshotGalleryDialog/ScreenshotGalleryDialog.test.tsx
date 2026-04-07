import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createGameScreenshot } from '@/test/factories';

import { ScreenshotGalleryDialog } from './ScreenshotGalleryDialog';

describe('Component: ScreenshotGalleryDialog', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ScreenshotGalleryDialog
        screenshots={[createGameScreenshot({ type: 'title' })]}
        isOpen={true}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the dialog is open, displays all screenshots', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'title' }),
      createGameScreenshot({ id: 2, type: 'ingame' }),
    ];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByRole('presentation')).toHaveLength(2);
    });
  });

  it('given the dialog is closed, does not render any content', () => {
    // ARRANGE
    render(
      <ScreenshotGalleryDialog
        screenshots={[createGameScreenshot()]}
        isOpen={false}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('given there is a completion screenshot and the user has not beaten the game, shows the spoiler overlay', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'completion' })];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        hasBeatenGame={false}
      />,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /reveal completion screenshot/i })).toBeVisible();
    });
  });

  it('given there is a completion screenshot and the user has beaten the game, does not show the spoiler overlay', () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'completion' })];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        hasBeatenGame={true}
      />,
    );

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /reveal completion screenshot/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user clicks the reveal button on a completion screenshot, removes the spoiler overlay', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'completion' })];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        hasBeatenGame={false}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reveal completion screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(
        screen.queryByRole('button', { name: /reveal completion screenshot/i }),
      ).not.toBeInTheDocument();
    });
  });

  it('given a non-completion screenshot, does not show a spoiler overlay', () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame' })];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        hasBeatenGame={false}
      />,
    );

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /reveal completion screenshot/i }),
    ).not.toBeInTheDocument();
  });

  it('given the system has analog TV output, applies a 4:3 aspect ratio to images', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame' })];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        hasAnalogTvOutput={true}
      />,
    );

    // ASSERT
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      expect(image).toHaveStyle({ aspectRatio: '4 / 3' });
    });
  });

  it('given isPixelated is true, applies pixelated image rendering', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame' })];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        isPixelated={true}
      />,
    );

    // ASSERT
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      expect(image).toHaveStyle({ imageRendering: 'pixelated' });
    });
  });

  it('given a low-res screenshot, uses the original lossless URL', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({
        id: 1,
        type: 'ingame',
        width: 256,
        originalUrl: 'https://example.com/original.png',
        lgWebpUrl: 'https://example.com/lg.webp',
      }),
    ];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      expect(image).toHaveAttribute('src', 'https://example.com/original.png');
    });
  });

  it('given a high-res screenshot, uses the optimized WebP URL', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({
        id: 1,
        type: 'ingame',
        width: 640,
        originalUrl: 'https://example.com/original.png',
        lgWebpUrl: 'https://example.com/lg.webp',
      }),
    ];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      expect(image).toHaveAttribute('src', 'https://example.com/lg.webp');
    });
  });

  it('given a pixelated system, constrains the image container to an integer-scaled width', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame', width: 256, height: 224 })];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        isPixelated={true}
      />,
    );

    // ASSERT
    // floor(1024 / 256) = 4. 4 * 256 = 1024.
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      const container = image.parentElement!;
      expect(container).toHaveStyle({ maxWidth: '1024px' });
    });
  });

  it('given a pixelated screenshot wider than the container, does not apply integer scaling', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'ingame', width: 2048, height: 1536 }),
    ];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        isPixelated={true}
      />,
    );

    // ASSERT
    // floor(1024 / 2048) = 0, which is below 1, so no maxWidth is applied.
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      const container = image.parentElement!;
      expect(container.style.maxWidth).toEqual('');
    });
  });

  it('given the user clicks the close button, calls onOpenChange with false', async () => {
    // ARRANGE
    const onOpenChange = vi.fn();

    render(
      <ScreenshotGalleryDialog
        screenshots={[createGameScreenshot({ id: 1 })]}
        isOpen={true}
        onOpenChange={onOpenChange}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /close/i }));

    // ASSERT
    expect(onOpenChange).toHaveBeenCalledWith(false);
  });

  it('given an initialIndex greater than zero, scrolls to the target screenshot on open', async () => {
    // ARRANGE
    const scrollIntoViewSpy = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = scrollIntoViewSpy;

    const screenshots = [
      createGameScreenshot({ id: 1, type: 'title' }),
      createGameScreenshot({ id: 2, type: 'ingame' }),
      createGameScreenshot({ id: 3, type: 'completion' }),
    ];

    render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        initialIndex={2}
      />,
    );

    // ASSERT
    await waitFor(() => {
      expect(scrollIntoViewSpy).toHaveBeenCalledWith({ block: 'start' });
    });
  });

  it('given the user clicks the backdrop area around the images, calls onOpenChange with false', async () => {
    // ARRANGE
    const onOpenChange = vi.fn();

    render(
      <ScreenshotGalleryDialog
        screenshots={[createGameScreenshot({ id: 1 })]}
        isOpen={true}
        onOpenChange={onOpenChange}
      />,
    );

    // ACT
    const dialogContent = screen.getByRole('dialog');
    await userEvent.click(dialogContent);

    // ASSERT
    expect(onOpenChange).toHaveBeenCalledWith(false);
  });

  it('given the dialog has a completion screenshot with a spoiler and is reopened, resets the revealed state', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'completion' })];

    const { rerender } = render(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        hasBeatenGame={false}
      />,
    );

    // ACT
    // ... reveal the spoiler, then close and reopen ...
    await userEvent.click(screen.getByRole('button', { name: /reveal completion screenshot/i }));
    await waitFor(() => {
      expect(
        screen.queryByRole('button', { name: /reveal completion screenshot/i }),
      ).not.toBeInTheDocument();
    });

    rerender(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={false}
        onOpenChange={vi.fn()}
        hasBeatenGame={false}
      />,
    );

    rerender(
      <ScreenshotGalleryDialog
        screenshots={screenshots}
        isOpen={true}
        onOpenChange={vi.fn()}
        hasBeatenGame={false}
      />,
    );

    // ASSERT
    // ... the spoiler overlay should be back ...
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /reveal completion screenshot/i })).toBeVisible();
    });
  });
});
