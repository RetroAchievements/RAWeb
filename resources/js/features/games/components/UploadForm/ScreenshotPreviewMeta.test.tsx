import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { ScreenshotPreviewMeta } from './ScreenshotPreviewMeta';

describe('Component: ScreenshotPreviewMeta', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ScreenshotPreviewMeta width={320} height={240} isResolutionValid={true} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the resolution dimensions', () => {
    // ARRANGE
    render(<ScreenshotPreviewMeta width={320} height={240} isResolutionValid={true} />);

    // ASSERT
    expect(screen.getByText('320x240')).toBeVisible();
  });

  it('given the resolution is valid, shows a valid resolution message', () => {
    // ARRANGE
    render(<ScreenshotPreviewMeta width={320} height={240} isResolutionValid={true} />);

    // ASSERT
    expect(screen.queryByText(/invalid resolution/i)).not.toBeInTheDocument();
    expect(screen.getByText(/valid resolution/i)).toBeVisible();
  });

  it('given the resolution is invalid, shows an invalid resolution message', () => {
    // ARRANGE
    render(<ScreenshotPreviewMeta width={123} height={456} isResolutionValid={false} />);

    // ASSERT
    expect(screen.queryByText('Valid resolution')).not.toBeInTheDocument();
    expect(screen.getByText(/invalid resolution/i)).toBeVisible();
  });

  it('given the resolution is invalid, branches the explanation copy on supportsUpscaledScreenshots', () => {
    // ARRANGE
    const { rerender } = render(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        supportsUpscaledScreenshots={true}
      />,
    );

    // ASSERT
    expect(screen.getByText(/ideally at 2x or 3x internal resolution/i)).toBeVisible();
    expect(screen.queryByText(/not a desktop capture/i)).not.toBeInTheDocument();

    // ACT
    rerender(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        supportsUpscaledScreenshots={false}
      />,
    );

    // ASSERT
    expect(screen.getByText(/not a desktop capture/i)).toBeVisible();
    expect(screen.queryByText(/internal resolution/i)).not.toBeInTheDocument();
  });

  it('given a 1x capture on an upscaling-capable system, shows the 1x nudge', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        supportsUpscaledScreenshots={true}
        is1xCapture={true}
      />,
    );

    // ASSERT
    expect(screen.getByText(/1x capture, render at 2x or 3x/i)).toBeVisible();
  });

  it('given a 1x capture on a non-upscaling system, does not show the 1x nudge', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={256}
        height={224}
        isResolutionValid={true}
        supportsUpscaledScreenshots={false}
        is1xCapture={true}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/1x capture/i)).not.toBeInTheDocument();
  });

  it('given an upscaled capture (not 1x) on an upscaling-capable system, does not show the 1x nudge', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={640}
        height={480}
        isResolutionValid={true}
        supportsUpscaledScreenshots={true}
        is1xCapture={false}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/1x capture/i)).not.toBeInTheDocument();
  });

  it('given both 1x nudge and consistency warning conditions are true, only the 1x nudge renders', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        supportsUpscaledScreenshots={true}
        is1xCapture={true}
        hasConsistencyWarning={true}
        canonicalResolution="640x480"
      />,
    );

    // ASSERT
    expect(screen.getByText(/1x capture/i)).toBeVisible();
    expect(screen.queryByText(/doesn't match existing screenshots/i)).not.toBeInTheDocument();
  });

  it('given the resolution is valid but inconsistent with canonical screenshots, shows an advisory warning message', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        hasConsistencyWarning={true}
        canonicalResolution="256x224"
      />,
    );

    // ASSERT
    expect(screen.getByText(/valid resolution/i)).toBeVisible();
    expect(screen.getByText(/doesn't match existing screenshots \(256x224\)/i)).toBeVisible();
  });

  it('given the resolution is valid but inconsistent with mixed screenshots, shows a generic advisory message', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        hasConsistencyWarning={true}
      />,
    );

    // ASSERT
    expect(screen.getByText(/doesn't match existing screenshots/i)).toBeVisible();
  });

  it('given the resolution is invalid, decorates the invalid label as a tooltip trigger only when accepted sizes are available', () => {
    // ARRANGE
    const { rerender } = render(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        screenshotResolutions={[{ width: 320, height: 240 }]}
      />,
    );

    // ASSERT
    const decoratedLabel = screen.getByText(/invalid resolution/i);
    expect(decoratedLabel).toHaveClass('underline');
    expect(decoratedLabel).toHaveClass('decoration-dotted');

    // ACT
    rerender(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        screenshotResolutions={[]}
      />,
    );

    // ASSERT
    expect(screen.getByText(/invalid resolution/i)).not.toHaveClass('underline');
  });

  it('given the user hovers the invalid label on a non-upscaling system, the tooltip shows the native list verbatim', async () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        screenshotResolutions={[
          { width: 256, height: 224 },
          { width: 256, height: 240 },
        ]}
        supportsUpscaledScreenshots={false}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByText(/invalid resolution/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText('256x224, 256x240').length).toBeGreaterThan(0);
    });
  });

  it('given the user hovers the invalid label on an upscaling-capable system, the tooltip shows the native list plus the 2x/3x clause', async () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        screenshotResolutions={[{ width: 320, height: 240 }]}
        supportsUpscaledScreenshots={true}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByText(/invalid resolution/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText('320x240').length).toBeGreaterThan(0);
      expect(screen.getAllByText(/or 2x or 3x of any of these/i).length).toBeGreaterThan(0);
    });
  });

  it('given the user hovers the invalid label, the tooltip sorts native resolutions by width and then height', async () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        screenshotResolutions={[
          { width: 640, height: 480 },
          { width: 256, height: 240 },
          { width: 320, height: 224 },
          { width: 256, height: 224 },
        ]}
        supportsUpscaledScreenshots={false}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByText(/invalid resolution/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText('256x224, 256x240, 320x224, 640x480').length).toBeGreaterThan(0);
    });
  });
});
