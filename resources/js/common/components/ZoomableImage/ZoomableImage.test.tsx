import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { ZoomableImage } from './ZoomableImage';

describe('Component: ZoomableImage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ZoomableImage src="test-image.jpg" alt={'test alt text' as TranslatedString}>
        <span>Click me</span>
      </ZoomableImage>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user clicks on the trigger element, displays the full-size image', async () => {
    // ARRANGE
    render(
      <ZoomableImage src="test-image.jpg" alt={'test alt text' as TranslatedString}>
        <span>Click me</span>
      </ZoomableImage>,
    );

    // ACT
    await userEvent.click(screen.getByText(/click me/i));

    // ASSERT
    const fullSizeImage = screen.getByAltText(/test alt text/i);
    expect(fullSizeImage).toBeVisible();
    expect(fullSizeImage.getAttribute('src')).toEqual('test-image.jpg');
  });

  it('given isPixelated is true, applies the pixelated image rendering style', async () => {
    // ARRANGE
    render(
      <ZoomableImage
        src="test-image.jpg"
        alt={'test alt text' as TranslatedString}
        isPixelated={true}
      >
        <span>Click me</span>
      </ZoomableImage>,
    );

    // ACT
    await userEvent.click(screen.getByText(/click me/i));

    // ASSERT
    const fullSizeImage = screen.getByAltText(/test alt text/i);
    expect(fullSizeImage).toHaveStyle({ imageRendering: 'pixelated' });
  });

  it('given a low-res source at or below the crisp-edges width threshold, applies crisp-edges', async () => {
    // ARRANGE
    render(
      <ZoomableImage
        src="test-image.jpg"
        alt={'test alt text' as TranslatedString}
        isPixelated={false}
        srcWidth={320}
      >
        <span>Click me</span>
      </ZoomableImage>,
    );

    // ACT
    await userEvent.click(screen.getByText(/click me/i));

    // ASSERT
    const fullSizeImage = screen.getByAltText(/test alt text/i);
    expect(fullSizeImage).toHaveStyle({ imageRendering: 'crisp-edges' });
  });

  it('given a hi-res source above the crisp-edges width threshold, applies no imageRendering hint', async () => {
    // ARRANGE
    render(
      <ZoomableImage
        src="test-image.jpg"
        alt={'test alt text' as TranslatedString}
        isPixelated={false}
        srcWidth={1920}
      >
        <span>Click me</span>
      </ZoomableImage>,
    );

    // ACT
    await userEvent.click(screen.getByText(/click me/i));

    // ASSERT
    const fullSizeImage = screen.getByAltText(/test alt text/i);
    expect(fullSizeImage).not.toHaveStyle({ imageRendering: 'crisp-edges' });
    expect(fullSizeImage).not.toHaveStyle({ imageRendering: 'pixelated' });
  });
});
