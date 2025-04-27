import { faker } from '@faker-js/faker';

import { render } from '@/test';
import {
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createGameSet,
  createSystem,
} from '@/test/factories';

import { GameShowSidebarRoot } from './GameShowSidebarRoot';

describe('Component: GameShowSidebarRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet(),
        }),
      ],
    });

    const { container } = render(<GameShowSidebarRoot />, {
      pageProps: {
        game,
        hubs: [createGameSet()],
        playerAchievementChartBuckets: [],
        topAchievers: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });
});
