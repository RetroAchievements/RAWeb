import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';
import { createGame, createGameScreenshot, createSystem } from '@/test/factories';

import { GameScreenshotUploadDialog } from './GameScreenshotUploadDialog';

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

describe('Component: GameScreenshotUploadDialog', () => {
  beforeEach(() => {
    vi.stubGlobal(
      'Image',
      class MockImage {
        naturalWidth = 320;
        naturalHeight = 240;
        onload: (() => void) | null = null;
        onerror: ((error: unknown) => void) | null = null;

        set src(_value: string) {
          queueMicrotask(() => this.onload?.());
        }
      },
    );

    URL.createObjectURL = vi.fn().mockReturnValue('blob:test');
    URL.revokeObjectURL = vi.fn();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
          screenshotUploadConsistency: null,
          screenshotUploadStatuses: {},
          screenshotUploadPendingCount: 0,
          screenshotUploadUserSubmissions: [],
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the dialog is open, shows the title, three screenshot type slots, and the submit button', () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 0,
        screenshotUploadUserSubmissions: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/upload screenshot/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /title/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /in-game/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /completion/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
  });

  it('given the dialog is closed, does not render any content', () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={false} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 0,
        screenshotUploadUserSubmissions: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('given the user clicks a slot, selects that type', async () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 0,
        screenshotUploadUserSubmissions: [],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /title/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /title/i })).toHaveClass('border-neutral-200');
  });

  it('given screenshot upload statuses are provided, shows the status indicator only for slots without a status', () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: { title: { count: 1, hasResolutionIssues: false } },
        screenshotUploadPendingCount: 0,
        screenshotUploadUserSubmissions: [],
      },
    });

    // ASSERT
    const neededLabels = screen.getAllByText(/needed/i);
    expect(neededLabels).toHaveLength(2);
  });

  it('given there are existing user submissions, shows the pending submissions list', () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 1,
        screenshotUploadUserSubmissions: [
          createGameScreenshot({ id: 1, type: 'ingame', width: 320, height: 240 }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/your submissions/i)).toBeInTheDocument();
    expect(screen.getByText('320x240')).toBeInTheDocument();
    expect(screen.queryByText(/no pending submissions/i)).not.toBeInTheDocument();
  });

  it('given there are no user submissions, does not show the pending submissions heading', () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 0,
        screenshotUploadUserSubmissions: [],
      },
    });

    // ASSERT
    expect(screen.queryByText(/your submissions/i)).not.toBeInTheDocument();
  });

  it('given the pending count is at or above 150, shows a warning message', () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 150,
        screenshotUploadUserSubmissions: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/150 of 200/i)).toBeVisible();
  });

  it('given the pending count is below 150, does not show a warning message', () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 149,
        screenshotUploadUserSubmissions: [],
      },
    });

    // ASSERT
    expect(screen.queryByText(/of 200/i)).not.toBeInTheDocument();
  });

  it('given the user uploads a screenshot successfully, adds it to the submissions list', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: createGameScreenshot({ id: 99, type: 'ingame', width: 256, height: 224 }),
    });

    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 0,
        screenshotUploadUserSubmissions: [],
      },
    });

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, new File(['test'], 'screenshot.png', { type: 'image/png' }));
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/your submissions/i)).toBeInTheDocument();
    });
  });

  it('passes screenshot upload consistency data through to the preview warning state', async () => {
    // ARRANGE
    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({
          system: createSystem({ screenshotResolutions: [{ width: 320, height: 240 }] }),
        }),
        screenshotUploadConsistency: {
          existingResolutions: [{ width: 256, height: 224 }],
          canonicalResolution: '256x224',
        },
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 0,
        screenshotUploadUserSubmissions: [],
      },
    });

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, new File(['test'], 'screenshot.png', { type: 'image/png' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/valid resolution/i)).toBeVisible();
      expect(screen.getByText(/doesn't match existing screenshots \(256x224\)/i)).toBeVisible();
    });
  });

  it('given the user cancels a submission and confirms, removes it from the list and calls the delete endpoint', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: { success: true } });

    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ id: 10, system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 1,
        screenshotUploadUserSubmissions: [
          createGameScreenshot({ id: 55, type: 'ingame', width: 320, height: 240 }),
        ],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /cancel submission/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByText('320x240')).not.toBeInTheDocument();
    });

    expect(deleteSpy).toHaveBeenCalledWith(
      route('api.game-screenshot.destroy', { game: 10, gameScreenshot: 55 }),
    );
  });

  it('given the user cancels a submission but the delete fails, shows an error toast', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    vi.spyOn(axios, 'delete').mockRejectedValueOnce(new Error('Network error'));

    render(<GameScreenshotUploadDialog isOpen={true} onOpenChange={vi.fn()} />, {
      pageProps: {
        game: createGame({ system: createSystem({ screenshotResolutions: [] }) }),
        screenshotUploadConsistency: null,
        screenshotUploadStatuses: {},
        screenshotUploadPendingCount: 1,
        screenshotUploadUserSubmissions: [
          createGameScreenshot({ id: 55, type: 'ingame', width: 320, height: 240 }),
        ],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /cancel submission/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });
});
