import { render, screen } from '@/test';

import { UnsubscribeUndoSuccessCard } from './UnsubscribeUndoSuccessCard';

describe('Component: UnsubscribeUndoSuccessCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UnsubscribeUndoSuccessCard />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the subscription restored message', () => {
    // ARRANGE
    render(<UnsubscribeUndoSuccessCard />);

    // ASSERT
    expect(screen.getByText(/your subscription has been restored/i)).toBeVisible();
  });

  it('displays the continue receiving notifications message', () => {
    // ARRANGE
    render(<UnsubscribeUndoSuccessCard />);

    // ASSERT
    expect(screen.getByText(/you will continue receiving notifications as before/i)).toBeVisible();
  });

  it('displays the manage email preferences link with correct href', () => {
    // ARRANGE
    render(<UnsubscribeUndoSuccessCard />);

    // ASSERT
    const link = screen.getByRole('link', { name: /manage all email preferences/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', 'settings.show');
  });
});
