import { render, screen } from '@/test';

import { ResultItem } from './ResultItem';

describe('Component: ResultItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ResultItem href="/test" isInertiaLink={true}>
        children
      </ResultItem>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    render(
      <ResultItem href="/test" isInertiaLink={false}>
        Test Content
      </ResultItem>,
    );

    // ASSERT
    expect(screen.getByText(/test content/i)).toBeVisible();
  });

  it('displays an accessible link with the correct href', () => {
    // ARRANGE
    render(
      <ResultItem href="/games/123" isInertiaLink={false}>
        Game Link
      </ResultItem>,
    );

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /game link/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', '/games/123');
  });
});
