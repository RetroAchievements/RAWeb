import { render, screen } from '@/test';

import { ShortcodeQuote } from './ShortcodeQuote';

describe('Component: ShortcodeQuote', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeQuote>Test content</ShortcodeQuote>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a simple string child, renders it inside a span with the quotedtext class', () => {
    // ARRANGE
    render(<ShortcodeQuote>test content</ShortcodeQuote>);

    // ASSERT
    const spanEl = screen.getByText(/test content/i);

    expect(spanEl).toBeVisible();
    expect(spanEl).toHaveClass('quotedtext');
  });
});
