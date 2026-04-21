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
});
