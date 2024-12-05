/* eslint-disable testing-library/no-container -- needed specifically for this test */

import { render } from '@/test';

import { PageMetaDescription } from './PageMetaDescription';

console.error = vi.fn();

describe('Component: PageMetaDescription', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PageMetaDescription content="Test description" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a description, renders both meta tags with the content', () => {
    // ARRANGE
    const description = 'This is a test description';
    const { container } = render(<PageMetaDescription content={description} />);

    // ASSERT
    const metaDescription = container.querySelector('meta[name="description"]');
    const ogDescription = container.querySelector('meta[name="og:description"]');

    expect(metaDescription).toHaveAttribute('content', description);
    expect(ogDescription).toHaveAttribute('content', description);
  });

  it('given a description longer than 209 characters, logs an error to the console', () => {
    // ARRANGE
    const consoleSpy = vi.spyOn(console, 'error');
    const longDescription = 'a'.repeat(210);

    // ACT
    render(<PageMetaDescription content={longDescription} />);

    // ASSERT
    expect(consoleSpy).toHaveBeenCalledWith(
      'The description content for this page is too long. Please shorten it.',
    );
  });

  it('given a description shorter than 210 characters, does not log an error', () => {
    // ARRANGE
    const consoleSpy = vi.spyOn(console, 'error');
    const shortDescription = 'a'.repeat(209);

    // ACT
    render(<PageMetaDescription content={shortDescription} />);

    // ASSERT
    expect(consoleSpy).not.toHaveBeenCalled();
  });
});
