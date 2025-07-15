import { render, screen } from '@/test';
import {
  createGame,
  createHomePageProps,
  createStaticGameAward,
  createUser,
} from '@/test/factories';

import { RecentGameAwards } from './RecentGameAwards';

describe('Component: RecentGameAwards', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentGameAwards />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no recent set mastered, does not show the mastered award', () => {
    // ARRANGE
    render(<RecentGameAwards />, {
      pageProps: createHomePageProps({
        mostRecentGameMastered: null,
      }),
    });

    // ASSERT
    expect(screen.queryByText(/most recent set mastered/i)).not.toBeInTheDocument();
  });

  it('given there is no recent game beaten, does not show the beaten award', () => {
    // ARRANGE
    render(<RecentGameAwards />, {
      pageProps: createHomePageProps({
        mostRecentGameBeaten: null,
      }),
    });

    // ASSERT
    expect(screen.queryByText(/most recent game beaten/i)).not.toBeInTheDocument();
  });

  it('given there is a most recent beaten award, displays the award metadata', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });
    const user = createUser({ displayName: 'Scott' });

    render(<RecentGameAwards />, {
      pageProps: createHomePageProps({
        mostRecentGameBeaten: createStaticGameAward({ game, user }),
      }),
    });

    // ASSERT
    expect(screen.getByText(/most recent game beaten/i)).toBeVisible();
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
    expect(screen.getByText(/scott/i)).toBeVisible();
  });

  it('given there is a most recent mastered award, displays the award metadata', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });
    const user = createUser({ displayName: 'Scott' });

    render(<RecentGameAwards />, {
      pageProps: createHomePageProps({
        mostRecentGameMastered: createStaticGameAward({ game, user }),
      }),
    });

    // ASSERT
    expect(screen.getByText(/most recent set mastered/i)).toBeVisible();
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
    expect(screen.getByText(/scott/i)).toBeVisible();
  });
});
