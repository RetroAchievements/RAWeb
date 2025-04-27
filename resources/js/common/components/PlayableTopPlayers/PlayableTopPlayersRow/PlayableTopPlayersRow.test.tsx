import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGameTopAchiever } from '@/test/factories';

import { PlayableTopPlayersRow } from './PlayableTopPlayersRow';

// Suppress JSDOM errors from rendering rows without a table.
// It only applies to our test environment.
console.error = vi.fn();

describe('Component: PlayableTopPlayersRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PlayableTopPlayersRow
        awardKind={null}
        listKind="latest-masters"
        numMasters={10}
        player={createGameTopAchiever({ userDisplayName: 'Scott' })}
        playerIndex={0}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the list kind is latest-masters, shows the date of last unlock', () => {
    // ARRANGE
    render(
      <PlayableTopPlayersRow
        awardKind={null}
        listKind="latest-masters"
        numMasters={10}
        player={createGameTopAchiever({
          lastUnlockHardcoreAt: '2024-01-01T00:00:00.000Z',
        })}
        playerIndex={0}
      />,
    );

    // ASSERT
    expect(screen.getByText(/jan 1, 2024/i)).toBeVisible();
    expect(screen.queryByText('1000')).not.toBeInTheDocument();
  });

  it('given the list kind is most-points-earned, shows the points', () => {
    // ARRANGE
    render(
      <PlayableTopPlayersRow
        awardKind={null}
        listKind="most-points-earned"
        numMasters={10}
        player={createGameTopAchiever({
          lastUnlockHardcoreAt: '2024-01-01T00:00:00.000Z',
          pointsHardcore: 1000,
        })}
        playerIndex={0}
      />,
    );

    // ASSERT
    expect(screen.getByText('1000')).toBeVisible();
    expect(screen.queryByText(/jan 1, 2024/i)).not.toBeInTheDocument();
  });

  it('given the list kind is latest-masters, calculates row number by subtracting index from total masters', () => {
    // ARRANGE
    render(
      <PlayableTopPlayersRow
        awardKind={null}
        listKind="latest-masters"
        numMasters={10} // !!
        player={createGameTopAchiever()}
        playerIndex={2} // !!
      />,
    );

    // ASSERT
    expect(screen.getByText('8')).toBeVisible(); // !! 10 - 2 = 8
  });
});
