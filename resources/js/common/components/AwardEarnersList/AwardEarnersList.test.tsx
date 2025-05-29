import { AwardType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import {
  createGame,
  createPaginatedData,
  createPlayerBadge,
  createRankedGameTopAchiever,
  createUser,
} from '@/test/factories';

import { AwardEarnersList } from './AwardEarnersList';

describe('Component: AwardEarnersList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameAwardEarnersPageProps>(
      <AwardEarnersList />,
      {
        pageProps: {
          paginatedUsers: createPaginatedData([createRankedGameTopAchiever()]),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no achievers, renders nothing', () => {
    // ARRANGE
    render<App.Platform.Data.GameAwardEarnersPageProps>(<AwardEarnersList />, {
      pageProps: {
        paginatedUsers: createPaginatedData([]),
      },
    });

    // ASSERT
    expect(screen.queryByText('Rank')).not.toBeInTheDocument();
  });

  it('given there are masteries, displays them', () => {
    // ARRANGE
    const user = createUser();
    render<App.Platform.Data.GameAwardEarnersPageProps>(<AwardEarnersList />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([
          createRankedGameTopAchiever({
            user,
            score: 753,
            badge: createPlayerBadge({
              awardType: AwardType.Mastery,
              awardDataExtra: 1,
              awardDate: '2024-11-01 05:55:55',
            }),
          }),
        ]),
      },
    });

    // ASSERT
    expect(screen.getByText(user.displayName)).toBeVisible();
    expect(screen.getByText('Nov 1, 2024 5:55 AM')).toBeVisible();
    expect(screen.getByText('Mastered')).toBeVisible();
    expect(screen.queryByText('753')).not.toBeInTheDocument();
  });

  it('given there are beats, displays them', () => {
    // ARRANGE
    const user = createUser();
    render<App.Platform.Data.GameAwardEarnersPageProps>(<AwardEarnersList />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([
          createRankedGameTopAchiever({
            user,
            score: 222,
            badge: createPlayerBadge({
              awardType: AwardType.GameBeaten,
              awardDataExtra: 1,
              awardDate: '2024-11-01 05:55:55',
            }),
          }),
        ]),
      },
    });

    // ASSERT
    expect(screen.getByText(user.displayName)).toBeVisible();
    expect(screen.getByText('222')).toBeVisible();
    expect(screen.getByText('Beaten')).toBeVisible();
  });

  it('given there are non-beats, displays them', () => {
    // ARRANGE
    const user = createUser();
    render<App.Platform.Data.GameAwardEarnersPageProps>(<AwardEarnersList />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([createRankedGameTopAchiever({ user, score: 59 })]),
      },
    });

    // ASSERT
    expect(screen.getByText(user.displayName)).toBeVisible();
    expect(screen.getByText('59')).toBeVisible();
    expect(screen.queryByText('Beaten')).not.toBeInTheDocument();
  });
});
