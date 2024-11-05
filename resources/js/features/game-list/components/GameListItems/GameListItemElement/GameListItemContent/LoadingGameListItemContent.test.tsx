import { render, screen } from '@/test';

import { LoadingGameListItemContent } from './LoadingGameListItemContent';

describe('Component: LoadingGameListItemContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<LoadingGameListItemContent />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays skeleton loaders with appropriate aria attributes', () => {
    // ARRANGE
    render(<LoadingGameListItemContent />);

    // ASSERT
    expect(screen.getByRole('status', { name: /loading/i })).toBeVisible();
  });

  it('only has a single loading status, so it does not yell at screen reader users', () => {
    // ARRANGE
    render(<LoadingGameListItemContent />);

    // ASSERT
    expect(screen.getAllByRole('status')).toHaveLength(1);
  });

  it('by default displays a bottom border skeleton', () => {
    // ARRANGE
    render(<LoadingGameListItemContent />);

    // ASSERT
    expect(screen.getByTestId('bottom-border')).toBeVisible();
  });

  it('conditionally renders the bottom border skeleton based on the isLastItem prop', () => {
    // ARRANGE
    render(<LoadingGameListItemContent isLastItem={true} />);

    // ASSERT
    expect(screen.queryByTestId('bottom-border')).not.toBeInTheDocument();
  });
});
