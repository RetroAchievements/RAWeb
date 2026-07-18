import { render, screen } from '@/test';

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

  it('given the resolution is invalid on an upscaling-capable system, shows the upscaling explanation', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        supportsUpscaledScreenshots={true}
      />,
    );

    // ASSERT
    expect(screen.getByText(/native, 2x, or 3x internal resolution/i)).toBeVisible();
    expect(screen.getByText(/not a desktop capture or manual resize/i)).toBeVisible();
  });

  it('given the resolution is invalid on a non-upscaling system, shows the native-only explanation', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={1920}
        height={1080}
        isResolutionValid={false}
        supportsUpscaledScreenshots={false}
      />,
    );

    // ASSERT
    expect(
      screen.getByText(/at native resolution, not a desktop capture or manual resize/i),
    ).toBeVisible();
    expect(screen.queryByText(/2x, or 3x/i)).not.toBeInTheDocument();
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

  it('given both 1x nudge and consistency nudge conditions are true, only the 1x nudge renders', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        supportsUpscaledScreenshots={true}
        is1xCapture={true}
        hasConsistencyWarning={true}
        selectedType="ingame"
      />,
    );

    // ASSERT
    expect(screen.getByText(/1x capture/i)).toBeVisible();
    expect(screen.queryByText(/more likely to be accepted/i)).not.toBeInTheDocument();
  });

  it('given the user is uploading an in-game screenshot with a consistency warning, nudges them to also submit a title', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        hasConsistencyWarning={true}
        selectedType="ingame"
      />,
    );

    // ASSERT
    expect(screen.getByText(/valid resolution/i)).toBeVisible();
    expect(
      screen.getByText(/then submit a matching title screenshot at this resolution/i),
    ).toBeVisible();
  });

  it('given the user is uploading a title screenshot with a consistency warning, nudges them to also submit an in-game', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        hasConsistencyWarning={true}
        selectedType="title"
      />,
    );

    // ASSERT
    expect(
      screen.getByText(/then submit a matching in-game screenshot at this resolution/i),
    ).toBeVisible();
  });

  it('given the user is uploading a completion screenshot with a consistency warning, shows the generic matching nudge', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        hasConsistencyWarning={true}
        selectedType="completion"
      />,
    );

    // ASSERT
    expect(screen.getByText(/then submit matching screenshots at this resolution/i)).toBeVisible();
    expect(screen.queryByText(/title screenshot/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/in-game screenshot/i)).not.toBeInTheDocument();
  });

  it('given there is no consistency warning, does not render any matching-screenshot nudge', () => {
    // ARRANGE
    render(
      <ScreenshotPreviewMeta
        width={320}
        height={240}
        isResolutionValid={true}
        hasConsistencyWarning={false}
        selectedType="ingame"
      />,
    );

    // ASSERT
    expect(screen.queryByText(/more likely to be accepted/i)).not.toBeInTheDocument();
  });
});
