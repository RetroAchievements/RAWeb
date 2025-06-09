import { render, screen } from '@/test';

import { HelperFooter } from './HelperFooter';

describe('Component: HelperFooter', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<HelperFooter />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays all keyboard navigation hints', () => {
    // ARRANGE
    render(<HelperFooter />);

    // ASSERT
    expect(screen.getByText(/navigate/i)).toBeVisible();
    expect(screen.getByText(/select/i)).toBeVisible();
    expect(screen.getByText(/close/i)).toBeVisible();

    expect(screen.getByText('↑↓')).toBeVisible();
    expect(screen.getByText('↵')).toBeVisible();
    expect(screen.getByText('esc')).toBeVisible();
  });

  it('renders the RA icon', () => {
    // ARRANGE
    render(<HelperFooter />);

    // ACT
    const raIcon = screen.getByRole('img');

    // ASSERT
    expect(raIcon).toBeVisible();
    expect(raIcon).toHaveAttribute('src', '/assets/images/ra-icon.webp');
  });

  it('given the viewport is mobile, hides the keyboard navigation hints', () => {
    // ARRANGE
    // The navigation hints have sm:flex class, which means they're hidden on mobile by default.
    render(<HelperFooter />);

    // ACT
    const navigationContainer = screen.getByText(/navigate/i).closest('div');

    // ASSERT
    expect(navigationContainer).toHaveClass('hidden', 'sm:flex');
  });
});
