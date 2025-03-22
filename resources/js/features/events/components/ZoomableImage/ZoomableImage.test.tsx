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
});
