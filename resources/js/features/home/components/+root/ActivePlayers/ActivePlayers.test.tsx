import { render, screen } from '@/test';

import { ActivePlayers } from './ActivePlayers';

describe('Component: ActivePlayers', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ActivePlayers />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<ActivePlayers />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /active players/i })).toBeVisible();
  });

  it.todo('displays an empty state when there are no active players');
});
