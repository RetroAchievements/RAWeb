import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { fireEvent, render, screen, waitFor } from '@/test';
import { createGameScreenshot } from '@/test/factories';

import { UploadForm } from './UploadForm';
// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

function createMockImageFile(name = 'screenshot.png') {
  return new File(['test-image-data'], name, { type: 'image/png' });
}

describe('Component: UploadForm', () => {
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
      <UploadForm gameId={1} screenshotResolutions={[]} selectedType="ingame" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no file has been selected, disables the submit button', () => {
    // ARRANGE
    render(<UploadForm gameId={1} screenshotResolutions={[]} selectedType="ingame" />);

    // ASSERT
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
  });

  it('given the user selects a file, enables the submit button and shows the image preview', async () => {
    // ARRANGE
    render(<UploadForm gameId={1} screenshotResolutions={[]} selectedType="ingame" />);

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile());

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
      expect(screen.getByRole('img', { name: /preview/i })).toBeInTheDocument();
    });
  });

  it('given the user selects a file and then selects another, revokes the old preview URL', async () => {
    // ARRANGE
    render(<UploadForm gameId={1} screenshotResolutions={[]} selectedType="ingame" />);

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile('first.png'));
    await waitFor(() => {
      expect(screen.getByRole('img', { name: /preview/i })).toBeInTheDocument();
    });

    await userEvent.upload(fileInput, createMockImageFile('second.png'));

    // ASSERT
    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:test');
  });

  it('given a non-upscaling system, displays the capture tool guidance in the drop zone', () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
        supportsUpscaledScreenshots={false}
      />,
    );

    // ASSERT
    expect(
      screen.getByText(/use your emulator's screenshot tool\. don't manually resize/i),
    ).toBeVisible();
  });

  it('given an upscaling-capable system, displays the upscale nudge in the drop zone', () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
        supportsUpscaledScreenshots={true}
      />,
    );

    // ASSERT
    expect(
      screen.getByText(/upscaled screenshots look sharper\. render at 2x or 3x/i),
    ).toBeVisible();
  });

  it('given the preview is valid and matches the existing canonical resolution, does not show a consistency warning', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        screenshotUploadConsistency={{
          existingResolutions: [{ width: 320, height: 240 }],
          canonicalResolution: '320x240',
        }}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile());

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/valid resolution/i)).toBeVisible();
      expect(screen.queryByText(/more likely to be accepted/i)).not.toBeInTheDocument();
    });
  });

  it('given the preview is valid but differs from the canonical resolution, shows a slot-aware companion nudge', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        screenshotUploadConsistency={{
          existingResolutions: [{ width: 256, height: 224 }],
          canonicalResolution: '256x224',
        }}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile());

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/valid resolution/i)).toBeVisible();
      expect(
        screen.getByText(/then submit a matching title screenshot at this resolution/i),
      ).toBeVisible();
    });
  });

  it('given the user already has a pending submission at the previewed resolution, does not show the companion nudge', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        screenshotUploadConsistency={{
          existingResolutions: [{ width: 256, height: 224 }],
          canonicalResolution: '256x224',
        }}
        pendingSubmissions={[createGameScreenshot({ type: 'ingame', width: 320, height: 240 })]}
        selectedType="title"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile());

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/valid resolution/i)).toBeVisible();
    });
    expect(screen.queryByText(/more likely to be accepted/i)).not.toBeInTheDocument();
  });

  it('given the preview is 1px off from the canonical resolution, does not show a consistency warning', async () => {
    // ARRANGE
    vi.stubGlobal(
      'Image',
      class MockImage {
        naturalWidth = 257;
        naturalHeight = 224;
        onload: (() => void) | null = null;
        onerror: ((error: unknown) => void) | null = null;

        set src(_value: string) {
          queueMicrotask(() => this.onload?.());
        }
      },
    );

    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 256, height: 224 }]}
        screenshotUploadConsistency={{
          existingResolutions: [{ width: 256, height: 224 }],
          canonicalResolution: '256x224',
        }}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile());

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/valid resolution/i)).toBeVisible();
      expect(screen.queryByText(/more likely to be accepted/i)).not.toBeInTheDocument();
    });
  });

  it('given the preview is invalid, does not show a consistency warning', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 256, height: 224 }]}
        screenshotUploadConsistency={{
          existingResolutions: [{ width: 320, height: 240 }],
          canonicalResolution: '320x240',
        }}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile());

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/invalid resolution/i)).toBeVisible();
      expect(screen.queryByText(/more likely to be accepted/i)).not.toBeInTheDocument();
    });
  });

  it('given the user submits a valid file, makes the correct POST call to the server', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { id: 5, type: 'ingame' },
    });

    render(
      <UploadForm
        gameId={7}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, createMockImageFile());

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();

      const [url, formData, options] = postSpy.mock.calls[0];
      expect(url).toEqual(route('api.game-screenshot.store', { game: 7 }));
      expect(formData).toBeInstanceOf(FormData);
      expect((formData as FormData).get('type')).toEqual('ingame');
      expect(options).toEqual({ headers: { 'Content-Type': 'multipart/form-data' } });
    });
  });

  it('given the submission succeeds, shows a success toast, calls onSuccess, and clears the preview', async () => {
    // ARRANGE
    const screenshotResponse = { id: 5, type: 'ingame' };
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: screenshotResponse });

    const onSuccess = vi.fn();
    render(
      <UploadForm
        gameId={7}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
        onSuccess={onSuccess}
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, createMockImageFile());
    await waitFor(() => {
      expect(screen.getByRole('img', { name: /preview/i })).toBeInTheDocument();
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/screenshot submitted successfully/i)).toBeVisible();
      expect(screen.queryByRole('img', { name: /preview/i })).not.toBeInTheDocument();
    });

    expect(onSuccess).toHaveBeenCalledWith(screenshotResponse);
  });

  it('given the submission fails with a known error code, shows the translated error message', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: {
        status: 422,
        data: { error: 'duplicate_hash' },
      },
    });

    render(
      <UploadForm
        gameId={7}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, createMockImageFile());
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/already been uploaded/i)).toBeVisible();
    });
  });

  it('given the submission fails with an unknown error code, shows a generic error message', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: {
        status: 500,
        data: { message: 'Internal Server Error' },
      },
    });

    render(
      <UploadForm
        gameId={7}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, createMockImageFile());
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('given the file has an invalid resolution, shows a short form-error pointing to the preview', async () => {
    // ARRANGE
    vi.stubGlobal(
      'Image',
      class MockImage {
        naturalWidth = 999;
        naturalHeight = 888;
        onload: (() => void) | null = null;
        onerror: ((error: unknown) => void) | null = null;

        set src(_value: string) {
          queueMicrotask(() => this.onload?.());
        }
      },
    );

    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, createMockImageFile());
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/resolution doesn't match\. see the preview above/i)).toBeVisible();
    });
  });

  it('given the user drops an image file on the drop zone, sets the file as the form value', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { id: 5, type: 'ingame' },
    });

    render(
      <UploadForm
        gameId={7}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    // ACT
    const dropZone = screen.getByRole('button', { name: /drop your screenshot/i });
    const file = createMockImageFile();
    fireEvent.drop(dropZone, { dataTransfer: { files: [file], types: ['Files'] } });

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });

    // Verify it can submit after drop.
    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
  });

  it('given the image fails to load during preview, clears the preview dimensions', async () => {
    // ARRANGE
    vi.stubGlobal(
      'Image',
      class MockImage {
        naturalWidth = 0;
        naturalHeight = 0;
        onload: (() => void) | null = null;
        onerror: ((error: unknown) => void) | null = null;

        set src(_value: string) {
          queueMicrotask(() => this.onerror?.(new Error('load failed')));
        }
      },
    );

    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, createMockImageFile());

    // ASSERT
    // The preview image should still show (previewUrl is set), but
    // the resolution metadata should not be visible since dimensions are null.
    await waitFor(() => {
      expect(screen.queryByText(/valid resolution/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/invalid resolution/i)).not.toBeInTheDocument();
    });
  });

  it('given the user had a file selected and then clears it, removes the preview', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, createMockImageFile());
    await waitFor(() => {
      expect(screen.getByRole('img', { name: /preview/i })).toBeInTheDocument();
    });

    // ACT
    // Simulate the file input change event with no files (eg. user cancels the file picker).
    fireEvent.change(fileInput, { target: { files: [] } });

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
    });
  });

  it('given upscaled screenshots are not supported and the user selects a JPEG via the file input, rejects it with a toast', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
        supportsUpscaledScreenshots={false}
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    const jpegFile = new File(['test'], 'screenshot.jpg', { type: 'image/jpeg' });

    // ACT
    fireEvent.change(fileInput, { target: { files: [jpegFile] } });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
    await waitFor(() => {
      expect(screen.getByText(/only accepts PNG/i)).toBeVisible();
    });
  });

  it('given upscaled screenshots are not supported and the user drops a JPEG, rejects it with a toast', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
        supportsUpscaledScreenshots={false}
      />,
    );

    const dropZone = screen.getByRole('button', { name: /drop your screenshot/i });
    const jpegFile = new File(['test'], 'screenshot.jpg', { type: 'image/jpeg' });

    // ACT
    fireEvent.drop(dropZone, { dataTransfer: { files: [jpegFile], types: ['Files'] } });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
    await waitFor(() => {
      expect(screen.getByText(/only accepts PNG/i)).toBeVisible();
    });
  });

  it('given upscaled screenshots are supported and the user drops an AVIF, rejects it with a toast', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
        supportsUpscaledScreenshots={true}
      />,
    );

    const dropZone = screen.getByRole('button', { name: /drop your screenshot/i });
    const avifFile = new File(['test'], 'screenshot.avif', { type: 'image/avif' });

    // ACT
    fireEvent.drop(dropZone, { dataTransfer: { files: [avifFile], types: ['Files'] } });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
    await waitFor(() => {
      expect(screen.getByText(/only PNG, JPEG, and WebP/i)).toBeVisible();
    });
  });

  it('given upscaled screenshots are supported and the user drops a JPEG, accepts it', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
        supportsUpscaledScreenshots={true}
      />,
    );

    const dropZone = screen.getByRole('button', { name: /drop your screenshot/i });
    const jpegFile = new File(['test'], 'screenshot.jpg', { type: 'image/jpeg' });

    // ACT
    fireEvent.drop(dropZone, { dataTransfer: { files: [jpegFile], types: ['Files'] } });

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });
  });

  it('given the user picks a file larger than 6 MB via the file input, rejects it with a toast', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    const oversizedFile = new File([new Uint8Array(7 * 1024 * 1024)], 'huge.png', {
      type: 'image/png',
    });

    // ACT
    fireEvent.change(fileInput, { target: { files: [oversizedFile] } });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
    await waitFor(() => {
      expect(screen.getByText(/this screenshot is 7\.0 MB\. the maximum is 6 MB/i)).toBeVisible();
    });
  });

  it('given the user drops a file larger than 6 MB on the drop zone, rejects it with a toast', async () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const dropZone = screen.getByRole('button', { name: /drop your screenshot/i });
    const oversizedFile = new File([new Uint8Array(7 * 1024 * 1024)], 'huge.png', {
      type: 'image/png',
    });

    // ACT
    fireEvent.drop(dropZone, { dataTransfer: { files: [oversizedFile], types: ['Files'] } });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
    await waitFor(() => {
      expect(screen.getByText(/this screenshot is 7\.0 MB\. the maximum is 6 MB/i)).toBeVisible();
    });
  });

  it('given the user drops a non-image file, does not set it as the form value', () => {
    // ARRANGE
    render(
      <UploadForm
        gameId={1}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    const dropZone = screen.getByRole('button', { name: /drop your screenshot/i });
    const textFile = new File(['hello'], 'notes.txt', { type: 'text/plain' });

    // ACT
    fireEvent.drop(dropZone, { dataTransfer: { files: [textFile], types: ['Files'] } });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeDisabled();
  });

  it('given the selectedType changes, syncs the form type value', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { id: 5, type: 'title' },
    });

    const { rerender } = render(
      <UploadForm
        gameId={7}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="ingame"
      />,
    );

    // ACT
    rerender(
      <UploadForm
        gameId={7}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        selectedType="title"
      />,
    );

    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    await userEvent.upload(fileInput, createMockImageFile());
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /submit screenshot/i })).toBeEnabled();
    });

    await userEvent.click(screen.getByRole('button', { name: /submit screenshot/i }));

    // ASSERT
    await waitFor(() => {
      const [, formData] = postSpy.mock.calls[0];
      expect((formData as FormData).get('type')).toEqual('title');
    });
  });
});
