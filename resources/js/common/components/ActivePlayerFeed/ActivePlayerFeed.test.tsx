import userEvent from '@testing-library/user-event';
import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { render, screen, waitFor } from '@/test';
import { createActivePlayer, createGame, createPaginatedData, createUser } from '@/test/factories';

import { ActivePlayerFeed } from './ActivePlayerFeed';

describe('Component: ActivePlayerFeed', () => {
  beforeEach(() => {
    mockAllIsIntersecting(false);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ActivePlayerFeed initialActivePlayers={createPaginatedData([])} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no active players, renders the correct label', () => {
    // ARRANGE
    render(
      <ActivePlayerFeed
        initialActivePlayers={createPaginatedData([], { total: 0, unfilteredTotal: 0 })}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('players-label')).toHaveTextContent(/viewing 0 players in-game/i);
  });

  it('given there are active players, renders the correct label', () => {
    // ARRANGE
    render(
      <ActivePlayerFeed
        initialActivePlayers={createPaginatedData([], { total: 20, unfilteredTotal: 4000 })}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('players-label')).toHaveTextContent(
      /viewing 0 of 4,000 players in-game/i, // it's 0 because `items` is empty
    );
  });

  it('displays active players correctly', () => {
    // ARRANGE
    const user = createUser({
      displayName: 'Scott',
      richPresenceMsg: 'Playing Sonic the Hedgehog',
    });
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(
      <ActivePlayerFeed
        initialActivePlayers={createPaginatedData([createActivePlayer({ user, game })], {
          total: 1,
          unfilteredTotal: 4000,
        })}
      />,
    );

    // ASSERT
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
    expect(screen.getByRole('img', { name: /scott/i })).toBeVisible();
    expect(screen.getByText(/playing sonic the hedgehog/i)).toBeVisible();
  });

  it('supports displaying a search bar by default', async () => {
    // ARRANGE
    render(<ActivePlayerFeed initialActivePlayers={createPaginatedData([])} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /search active players/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('textbox', { name: /search/i })).toBeVisible();
    });
  });

  it('given the component is configured to not have a search bar, does not render a search bar', () => {
    // ARRANGE
    render(
      <ActivePlayerFeed initialActivePlayers={createPaginatedData([])} hasSearchBar={false} />,
    );

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /search active players/i }),
    ).not.toBeInTheDocument();
    expect(screen.queryByRole('textbox', { name: /search/i })).not.toBeInTheDocument();
  });
});
