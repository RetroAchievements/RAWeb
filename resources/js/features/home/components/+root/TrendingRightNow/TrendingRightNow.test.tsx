import { render, screen } from '@/test';
import { createGame, createHomePageProps, createTrendingGame } from '@/test/factories';

import { TrendingRightNow } from './TrendingRightNow';

describe('Component: TrendingRightNow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TrendingRightNow />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays trending games', () => {
    // ARRANGE
    const trendingGames = [
      createTrendingGame({ game: createGame({ title: 'Super Mario Bros' }), playerCount: 1000 }),
      createTrendingGame(),
      createTrendingGame(),
      createTrendingGame(),
    ];

    render<App.Http.Data.HomePageProps>(<TrendingRightNow />, {
      pageProps: createHomePageProps({ trendingGames }),
    });

    // ASSERT
    expect(screen.getByText(/super mario bros/i)).toBeVisible();
    expect(screen.getByText(/1,000 players/i)).toBeVisible();
  });
});
