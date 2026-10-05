import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { TicketUserValue } from './TicketUserValue';

describe('Component: TicketUserValue', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TicketUserValue user={createUser()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an active user, displays their display name as a profile link', () => {
    // ARRANGE
    render(<TicketUserValue user={createUser({ displayName: 'PixelHero' })} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /PixelHero/i })).toBeVisible();
  });

  it('given a null user reference, renders a deleted user notification', () => {
    // ARRANGE
    render(<TicketUserValue user={null} />);

    // ASSERT
    expect(screen.getByText(/deleted user/i)).toBeVisible();
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });
});
