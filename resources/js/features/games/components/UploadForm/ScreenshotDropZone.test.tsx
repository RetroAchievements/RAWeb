import userEvent from '@testing-library/user-event';
import type { RefObject } from 'react';

import { fireEvent, render, screen } from '@/test';

import { ScreenshotDropZone } from './ScreenshotDropZone';

// Suppress RangeError stack overflow noise from userEvent.upload in happy-dom.
console.error = vi.fn();

function createFileInputRef(): RefObject<HTMLInputElement | null> {
  return { current: null };
}

describe('Component: ScreenshotDropZone', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no preview, shows the upload prompt', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
        hasPreview={false}
      />,
    );

    // ASSERT
    expect(screen.getByText(/drop your screenshot here, or click to browse/i)).toBeVisible();
  });

  it('given an upscaling-capable system, shows the upscale nudge in the empty state', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
        hasPreview={false}
        supportsUpscaledScreenshots={true}
      />,
    );

    // ASSERT
    expect(
      screen.getByText(/upscaled screenshots look sharper\. render at 2x or 3x/i),
    ).toBeVisible();
  });

  it('given a non-upscaling system, shows the capture-tool guidance in the empty state', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
        hasPreview={false}
        supportsUpscaledScreenshots={false}
      />,
    );

    // ASSERT
    expect(
      screen.getByText(/use your emulator's screenshot tool\. don't manually resize/i),
    ).toBeVisible();
    expect(screen.queryByText(/upscaled screenshots/i)).not.toBeInTheDocument();
  });

  it('given there is a preview, shows the preview image and replacement prompt', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl="https://example.com/preview.png"
        hasPreview={true}
      />,
    );

    // ASSERT
    expect(screen.getByRole('img', { name: /preview/i })).toBeInTheDocument();
    expect(screen.getByText(/click or drag to replace/i)).toBeInTheDocument();
  });

  it('given there is a preview with dimensions, shows the resolution metadata', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl="https://example.com/preview.png"
        hasPreview={true}
        previewDimensions={{ width: 320, height: 240 }}
      />,
    );

    // ASSERT
    expect(screen.getByText('320x240')).toBeInTheDocument();
    expect(screen.getByText(/valid resolution/i)).toBeInTheDocument();
  });

  it('given there is a preview without dimensions, does not show the resolution metadata', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl="https://example.com/preview.png"
        hasPreview={true}
        previewDimensions={null}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/valid resolution/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/invalid resolution/i)).not.toBeInTheDocument();
  });

  it('given upscaled screenshots are supported, sets the file input to accept multiple formats', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
        supportsUpscaledScreenshots={true}
      />,
    );

    // ASSERT
    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    expect(fileInput.accept).toEqual('.png,.jpeg,.jpg,.webp');
  });

  it('given upscaled screenshots are not supported, sets the file input to accept only PNG', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
        supportsUpscaledScreenshots={false}
      />,
    );

    // ASSERT
    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;
    expect(fileInput.accept).toEqual('.png');
  });

  it('given the user clicks the drop zone, triggers the file input click', async () => {
    // ARRANGE
    const fileInputRef = createFileInputRef();
    render(
      <ScreenshotDropZone fileInputRef={fileInputRef} isResolutionValid={true} previewUrl={null} />,
    );

    const clickSpy = vi.fn();
    fileInputRef.current!.click = clickSpy;

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(clickSpy).toHaveBeenCalledOnce();
  });

  it('given the user selects a file, calls onFileChange with the file', async () => {
    // ARRANGE
    const onFileChange = vi.fn();
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
        onFileChange={onFileChange}
      />,
    );

    const file = new File(['test'], 'screenshot.png', { type: 'image/png' });
    const fileInput = screen.getByLabelText(/upload screenshot file/i) as HTMLInputElement;

    // ACT
    await userEvent.upload(fileInput, file);

    // ASSERT
    expect(onFileChange).toHaveBeenCalledWith(file);
  });

  it('given the user drops a file on the drop zone, calls onDrop', () => {
    // ARRANGE
    const onDrop = vi.fn();
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
        onDrop={onDrop}
      />,
    );

    // ACT
    const dropZone = screen.getByRole('button');
    fireEvent.drop(dropZone, { dataTransfer: { files: [] } });

    // ASSERT
    expect(onDrop).toHaveBeenCalledOnce();
  });

  it('given the observed content resizes, updates the animated height', () => {
    // ARRANGE
    let observerCallback: ResizeObserverCallback | null = null;

    const originalResizeObserver = window.ResizeObserver;
    window.ResizeObserver = class MockResizeObserver {
      constructor(callback: ResizeObserverCallback) {
        observerCallback = callback;
      }
      observe() {}
      unobserve() {}
      disconnect() {}
    } as unknown as typeof ResizeObserver;

    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
      />,
    );

    // ACT
    observerCallback!([], {} as ResizeObserver);

    // ASSERT
    expect(observerCallback).not.toBeNull();

    window.ResizeObserver = originalResizeObserver;
  });

  it('given the user drags over and then leaves the drop zone, applies and then removes the drag-over styling', () => {
    // ARRANGE
    render(
      <ScreenshotDropZone
        fileInputRef={createFileInputRef()}
        isResolutionValid={true}
        previewUrl={null}
      />,
    );

    const dropZone = screen.getByRole('button');

    // ACT
    fireEvent.dragOver(dropZone);

    // ASSERT
    expect(dropZone).toHaveClass('border-neutral-400');

    // ACT
    fireEvent.dragLeave(dropZone);

    // ASSERT
    expect(dropZone).toHaveClass('border-neutral-700');
    expect(dropZone).not.toHaveClass('border-neutral-400');
  });
});
