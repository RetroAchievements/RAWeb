import { GameSetRequestsRoot } from '@/features/games/components/+requests/GameSetRequestsRoot';
import { render } from '@/test';
import { createGame, createSystem } from '@/test/factories';

describe('Component: GameSetRequestsRoot', () => {
  it('renders without crashing', () => {
    const game = createGame({
      badgeUrl: 'badge.jpg',
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    // ARRANGE
    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null,
        initialRequestors: [],
        game,
        totalCount: 0,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    // ASSERT
    expect(container).toBeTruthy();
  });
});
