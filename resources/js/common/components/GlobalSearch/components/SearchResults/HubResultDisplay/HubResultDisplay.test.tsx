import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { HubResultDisplay } from './HubResultDisplay';

describe('Component: HubResultDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const hub = createGameSet();

    const { container } = render(<HubResultDisplay hub={hub} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the hub badge with correct attributes', () => {
    // ARRANGE
    const hub = createGameSet({
      title: '[Series - Mario]',
      badgeUrl: 'https://example.com/mario-hub.png',
    });

    render(<HubResultDisplay hub={hub} />);

    // ACT
    const badge = screen.getByRole('img');

    // ASSERT
    expect(badge).toBeVisible();
    expect(badge).toHaveAttribute('src', 'https://example.com/mario-hub.png');
    expect(badge).toHaveAttribute('alt', '[Series - Mario]');
  });

  it('displays the hub title', () => {
    // ARRANGE
    const hub = createGameSet({ title: '[Series - Mario]' });

    render(<HubResultDisplay hub={hub} />);

    // ASSERT
    expect(screen.getByText(/series - mario/i)).toBeVisible();
    expect(screen.queryByText(/]/i)).not.toBeInTheDocument();
  });

  it('given a hub with one game, displays singular game count', () => {
    // ARRANGE
    const hub = createGameSet({ gameCount: 1 });

    render(<HubResultDisplay hub={hub} />);

    // ASSERT
    expect(screen.getByText(/1 game$/i)).toBeVisible();
  });

  it('given a hub with multiple games, displays plural game count', () => {
    // ARRANGE
    const hub = createGameSet({ gameCount: 42 });

    render(<HubResultDisplay hub={hub} />);

    // ASSERT
    expect(screen.getByText(/42 games/i)).toBeVisible();
  });

  it('given a hub with zero games, displays zero games', () => {
    // ARRANGE
    const hub = createGameSet({ gameCount: 0 });

    render(<HubResultDisplay hub={hub} />);

    // ASSERT
    expect(screen.getByText(/0 games/i)).toBeVisible();
  });
});
