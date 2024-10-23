import { render, screen } from '@/test';

import { EmptyState } from './EmptyState';

describe('Component: EmptyState', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EmptyState>no results</EmptyState>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an image', () => {
    // ARRANGE
    render(<EmptyState>no results</EmptyState>);

    // ASSERT
    expect(screen.getByRole('img', { name: /empty state/i })).toBeVisible();
  });

  it('renders children', () => {
    // ARRANGE
    render(<EmptyState>no results</EmptyState>);

    // ASSERT
    expect(screen.getByText(/no results/i)).toBeVisible();
  });
});
