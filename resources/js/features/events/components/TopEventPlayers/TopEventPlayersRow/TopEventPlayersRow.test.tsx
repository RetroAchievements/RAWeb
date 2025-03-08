import { render, screen } from '@/test';
import { createGameTopAchiever } from '@/test/factories';

import { TopEventPlayersRow } from './TopEventPlayersRow';

// Suppress JSDOM errors from rendering rows without a table.
// It only applies to our test environment.
console.error = vi.fn();

describe('Component: TopEventPlayersRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TopEventPlayersRow
        listKind="latest-masters"
        numMasters={10}
        player={createGameTopAchiever()}
        playerIndex={0}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the list kind is latest-masters, shows the date of last unlock', () => {
    // ARRANGE
    render(
      <TopEventPlayersRow
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
      <TopEventPlayersRow
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
      <TopEventPlayersRow
        listKind="latest-masters"
        numMasters={10} // !!
        player={createGameTopAchiever()}
        playerIndex={2} // !!
      />,
    );

    // ASSERT
    expect(screen.getByText('8')).toBeVisible(); // !! 10 - 2 = 8
  });

  it('given the list kind is most-points-earned, calculates row number by adding 1 to index', () => {
    // ARRANGE
    render(
      <TopEventPlayersRow
        listKind="most-points-earned"
        numMasters={10}
        player={createGameTopAchiever()}
        playerIndex={2} // !!
      />,
    );

    // ASSERT
    expect(screen.getByText('3')).toBeVisible(); // !! 2 + 1 = 3
  });
});
