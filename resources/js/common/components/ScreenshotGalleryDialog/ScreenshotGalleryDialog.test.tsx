import userEvent from '@testing-library/user-event';

import { act, fireEvent, render, screen, waitFor } from '@/test';
import { createGameScreenshot } from '@/test/factories';

import { ScreenshotGalleryDialog } from './ScreenshotGalleryDialog';

describe('Component: ScreenshotGalleryDialog', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

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

  it('given a non-pixelated source at or below the crisp-edges width threshold, applies crisp-edges', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame', width: 320, height: 240 })];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      expect(image).toHaveStyle({ imageRendering: 'crisp-edges' });
    });
  });

  it('given a non-pixelated source above the crisp-edges width threshold, applies no imageRendering hint', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'ingame', width: 1920, height: 1440 }),
    ];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      expect(image).not.toHaveStyle({ imageRendering: 'crisp-edges' });
      expect(image).not.toHaveStyle({ imageRendering: 'pixelated' });
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

  it('given a high-res pixelated screenshot, uses the original lossless URL', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({
        id: 1,
        type: 'ingame',
        width: 560,
        originalUrl: 'https://example.com/original.png',
        lgWebpUrl: 'https://example.com/lg.webp',
      }),
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
    await waitFor(() => {
      const image = screen.getByRole('presentation');
      expect(image).toHaveAttribute('src', 'https://example.com/original.png');
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

  it('given a non-pixelated screenshot, renders a blurred placeholder image underneath', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({
        id: 1,
        type: 'ingame',
        placeholderUrl: 'https://example.com/blur.webp',
      }),
    ];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const allImages = screen.getAllByRole('presentation', { hidden: true });
      expect(allImages).toHaveLength(2);

      const [placeholder] = allImages;
      expect(placeholder).toHaveAttribute('src', 'https://example.com/blur.webp');
      expect(placeholder).toHaveAttribute('aria-hidden', 'true');
      expect(placeholder).toHaveAttribute('alt', '');
      expect(placeholder).toHaveStyle({ filter: 'blur(16px)', transform: 'scale(1.1)' });
    });
  });

  it('given a pixelated screenshot, does not render the blurred placeholder image', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({
        id: 1,
        type: 'ingame',
        placeholderUrl: 'https://example.com/blur.webp',
      }),
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
    await waitFor(() => {
      const allImages = screen.getAllByRole('presentation', { hidden: true });
      expect(allImages).toHaveLength(1);
    });
  });

  it('given a screenshot, reserves layout space with width and height on the real image', async () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, type: 'ingame', width: 1920, height: 1080 }),
    ];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const realImage = screen.getByRole('presentation');
      expect(realImage).toHaveAttribute('width', '1920');
      expect(realImage).toHaveAttribute('height', '1080');
      expect(realImage).toHaveAttribute('loading', 'lazy');
      expect(realImage).toHaveAttribute('decoding', 'async');
    });
  });

  it('given a non-pixelated screenshot has not loaded yet, hides the real image so the placeholder shows through', async () => {
    // ARRANGE
    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame' })];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const realImage = screen.getByRole('presentation');
      expect(realImage).toHaveClass('opacity-0');
    });
  });

  it('given the real image load fires more than once, replaces the placeholder hide timer', async () => {
    // ARRANGE
    vi.useFakeTimers();

    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame' })];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    const realImage = screen.getByRole('presentation');
    const setTimeoutSpy = vi.spyOn(window, 'setTimeout');
    const clearTimeoutSpy = vi.spyOn(window, 'clearTimeout');

    // ACT
    fireEvent.load(realImage);
    fireEvent.load(realImage);

    // ASSERT
    expect(clearTimeoutSpy).toHaveBeenCalledTimes(1);
    expect(setTimeoutSpy).toHaveBeenCalledTimes(2);
  });

  it('given the placeholder opacity transition does not fire, removes the placeholder after the fallback delay', async () => {
    // ARRANGE
    vi.useFakeTimers();

    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame' })];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    const realImage = screen.getByRole('presentation');

    // ACT
    fireEvent.load(realImage);

    act(() => {
      vi.advanceTimersByTime(550);
    });

    // ASSERT
    expect(screen.getAllByRole('presentation', { hidden: true })).toHaveLength(1);
  });

  it('given the real image was already loaded before ref attachment, marks it as loaded', async () => {
    // ARRANGE
    vi.spyOn(HTMLImageElement.prototype, 'complete', 'get').mockReturnValue(true);
    vi.spyOn(HTMLImageElement.prototype, 'naturalWidth', 'get').mockReturnValue(1);

    const screenshots = [createGameScreenshot({ id: 1, type: 'ingame' })];

    render(
      <ScreenshotGalleryDialog screenshots={screenshots} isOpen={true} onOpenChange={vi.fn()} />,
    );

    // ASSERT
    await waitFor(() => {
      const realImage = screen.getByRole('presentation');
      expect(realImage).not.toHaveClass('opacity-0');
    });
  });

  it('given a pixelated screenshot, snaps the real image in without an opacity fade', async () => {
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
      const realImage = screen.getByRole('presentation');
      expect(realImage).not.toHaveClass('opacity-0');
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

  it('given the user clicks the dark area around the images, calls onOpenChange with false', async () => {
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
    const scrollContainer = screen.getByRole('dialog').firstElementChild as HTMLElement;
    await userEvent.click(scrollContainer);

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
