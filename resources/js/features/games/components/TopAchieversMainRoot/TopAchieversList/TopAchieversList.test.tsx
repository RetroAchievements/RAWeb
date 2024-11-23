import { faker } from '@faker-js/faker';

import { render, screen, within } from '@/test';

import { createFactory } from '@/test/createFactory';
import { createGame, createPaginatedData, createPlayerBadge, createSystem, createUser } from '@/test/factories';

import { TopAchieversList, TopAchieversListContainerTestId } from './TopAchieversList';
import { AwardType } from '@/common/utils/generatedAppConstants';

export const createAchiever = createFactory<App.Platform.Data.GameTopAchiever>((faker) => {
  return {
    rank: 1,
    user: createUser(),
    score: faker.number.int({ min: 1, max: 100 }),
    badge: null,
  };
});

describe('Component: TopAchieversList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversList />, {
      pageProps: {
        paginatedUsers: createPaginatedData([createAchiever()]),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no achievers, renders nothing', () => {
    // ARRANGE
    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversList />, {
      pageProps: {
        paginatedUsers: createPaginatedData([]),
      },
    });

    // ASSERT
    expect(screen.queryByTestId(TopAchieversListContainerTestId)).not.toBeInTheDocument();
  });

  it('given there are masteries, displays them', () => {
    // ARRANGE
    const user = createUser();
    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversList />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([
          createAchiever({ user: user, score: 753, badge: createPlayerBadge({ awardType: AwardType.Mastery, awardDataExtra: 1, awardDate: '2024-11-01 05:55:55' }) })
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
    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversList />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([
          createAchiever({ user: user, score: 222, badge: createPlayerBadge({ awardType: AwardType.GameBeaten, awardDataExtra: 1, awardDate: '2024-11-01 05:55:55' }) })
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
    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversList />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([
          createAchiever({ user: user, score: 59 })
        ]),
      },
    });

    // ASSERT
    expect(screen.getByText(user.displayName)).toBeVisible();
    expect(screen.getByText('59')).toBeVisible();
    expect(screen.queryByText('Beaten')).not.toBeInTheDocument();
  });
});
