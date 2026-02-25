import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement, createGame, createSystem } from '@/test/factories';

import type { TabConfig } from '../../models';
import { AchievementTabs } from './AchievementTabsList';

describe('Component: AchievementTabs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const tabConfigs: TabConfig[] = [
      { value: 'comments', label: 'Comments' },
      { value: 'unlocks', label: 'Unlocks' },
    ];

    const { container } = render(
      <AchievementTabs tabConfigs={tabConfigs}>
        <p>tab content</p>
      </AchievementTabs>,
      {
        pageProps: {
          achievement: createAchievement({
            game: createGame({ system: createSystem() }),
          }),
          backingGame: null,
          gameAchievementSet: null,
          can: { createAchievementComments: false },
          isSubscribedToComments: false,
          numComments: 0,
          recentVisibleComments: [],
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    const tabConfigs: TabConfig[] = [{ value: 'comments', label: 'Comments' }];

    render(
      <AchievementTabs tabConfigs={tabConfigs}>
        <p>some tab content</p>
      </AchievementTabs>,
      {
        pageProps: {
          achievement: createAchievement({
            game: createGame({ system: createSystem() }),
          }),
          backingGame: null,
          gameAchievementSet: null,
          can: { createAchievementComments: false },
          isSubscribedToComments: false,
          numComments: 0,
          recentVisibleComments: [],
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/some tab content/i)).toBeVisible();
  });

  it('given a tab has a mobileLabel, uses it for the mobile tab trigger', () => {
    // ARRANGE
    const tabConfigs: TabConfig[] = [
      { value: 'comments', label: 'Comments' },
      { value: 'unlocks', label: 'Recent Unlocks', mobileLabel: 'Unlocks' },
    ];

    render(
      <AchievementTabs tabConfigs={tabConfigs}>
        <p>content</p>
      </AchievementTabs>,
      {
        pageProps: {
          achievement: createAchievement({
            game: createGame({ system: createSystem() }),
          }),
          backingGame: null,
          gameAchievementSet: null,
          can: { createAchievementComments: false },
          isSubscribedToComments: false,
          numComments: 0,
          recentVisibleComments: [],
        },
      },
    );

    // ASSERT
    const unlocksTabs = screen.getAllByRole('tab', { name: /unlocks/i });
    expect(unlocksTabs[0]).toHaveTextContent('Unlocks');
    expect(unlocksTabs[1]).toHaveTextContent('Recent Unlocks');
  });

  it('given a tab has no mobileLabel, falls back to the label for the mobile tab trigger', () => {
    // ARRANGE
    const tabConfigs: TabConfig[] = [{ value: 'comments', label: 'Comments' }];

    render(
      <AchievementTabs tabConfigs={tabConfigs}>
        <p>content</p>
      </AchievementTabs>,
      {
        pageProps: {
          achievement: createAchievement({
            game: createGame({ system: createSystem() }),
          }),
          backingGame: null,
          gameAchievementSet: null,
          can: { createAchievementComments: false },
          isSubscribedToComments: false,
          numComments: 0,
          recentVisibleComments: [],
        },
      },
    );

    // ASSERT
    const commentsTabs = screen.getAllByRole('tab', { name: /comments/i });
    expect(commentsTabs).toHaveLength(2);

    expect(commentsTabs[0]).toHaveTextContent('Comments');
    expect(commentsTabs[1]).toHaveTextContent('Comments');
  });

  it('given the user hovers over a desktop tab and then leaves, does not crash', async () => {
    // ARRANGE
    const tabConfigs: TabConfig[] = [
      { value: 'comments', label: 'Comments' },
      { value: 'unlocks', label: 'Unlocks' },
    ];

    render(
      <AchievementTabs tabConfigs={tabConfigs}>
        <p>content</p>
      </AchievementTabs>,
      {
        pageProps: {
          achievement: createAchievement({
            game: createGame({ system: createSystem() }),
          }),
          backingGame: null,
          gameAchievementSet: null,
          can: { createAchievementComments: false },
          isSubscribedToComments: false,
          numComments: 0,
          recentVisibleComments: [],
        },
      },
    );

    // ACT
    const desktopUnlocksTab = screen.getAllByRole('tab', { name: /unlocks/i })[1];
    await userEvent.hover(desktopUnlocksTab);
    await userEvent.unhover(desktopUnlocksTab);

    // ASSERT
    expect(desktopUnlocksTab).toBeVisible();
  });
});
