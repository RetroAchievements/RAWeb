import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementOfTheWeekProps,
  createEventAchievement,
  createGame,
  createHomePageProps,
  createRaEvent,
  createSystem,
} from '@/test/factories';

import { AchievementOfTheWeek } from './AchievementOfTheWeek';

describe('Component: AchievementOfTheWeek', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /achievement of the week/i })).toBeVisible();
  });

  it('given there is no achievement of the week data, does not crash', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek: null }),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no current event achievement, does not crash', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({
        achievementOfTheWeek: { currentEventAchievement: null } as any,
      }),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible link to the event page', () => {
    // ARRANGE
    const legacyGame = createGame();
    const event = createRaEvent({ legacyGame });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({
        achievementOfTheWeek: createAchievementOfTheWeekProps({
          currentEventAchievement: createEventAchievement({ event }),
        }),
      }),
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /view this year's event/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('event.show'));
  });

  it('given there is no accessible link to the event page, does not render a link', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: undefined });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({
        achievementOfTheWeek: createAchievementOfTheWeekProps({
          currentEventAchievement: createEventAchievement({ event }),
        }),
      }),
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /view this year's event/i })).not.toBeInTheDocument();
  });

  it('has a link to the achievement', () => {
    // ARRANGE
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: createAchievement({ id: 9, title: 'That Was Easy' }),
      }),
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    const linkEl = screen.getAllByRole('link', { name: /that was easy/i })[0];

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('achievement.show'));
  });

  it('has a link to the game', () => {
    // ARRANGE
    const system = createSystem({ name: 'Sega Genesis/Mega Drive', nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });
    const sourceAchievement = createAchievement({ game, id: 9, title: 'That Was Easy' });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
      }),
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    const linkEl = screen.getAllByRole('link', { name: /sonic the hedgehog/i })[0];

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('game.show'));
  });

  it('displays the achievement title and description', () => {
    // ARRANGE
    const system = createSystem({ name: 'Sega Genesis/Mega Drive', nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });
    const sourceAchievement = createAchievement({ game, id: 9, title: 'That Was Easy' });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
      }),
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    expect(screen.getByText(/that was easy/i)).toBeVisible();
    expect(screen.getByText(sourceAchievement.description!)).toBeVisible();
  });

  it('displays the game title and system name', () => {
    // ARRANGE
    const system = createSystem({ name: 'Sega Genesis/Mega Drive', nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });
    const sourceAchievement = createAchievement({ game, id: 9, title: 'That Was Easy' });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
      }),
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
    expect(screen.getByText('MD')).toBeVisible();
  });

  it('displays the remaining time', () => {
    // ARRANGE
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 64 * 60 * 60 * 1000); // 64 hours = 2.6 days
    const sourceAchievement = createAchievement({
      id: 9,
      title: 'That Was Easy',
      description: 'foo',
    });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
        activeThrough: tomorrow.toISOString(),
      }),
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    expect(screen.getByText(/ends/i)).toBeVisible();
    expect(screen.getByText(/in 2 days/i)).toBeVisible();
  });

  it('displays the end date', () => {
    // ARRANGE
    const sourceAchievement = createAchievement({
      id: 9,
      title: 'That Was Easy',
      description: 'foo',
    });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
        activeThrough: new Date('2030-04-08').toISOString(),
      }),
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: true, shouldAlwaysBypassContentWarnings: false },
          }),
        },
        ...createHomePageProps({ achievementOfTheWeek }),
      },
    });

    // ASSERT
    expect(screen.getByText(/ends/i)).toBeVisible();
    expect(screen.getByText(/Apr 08, 2030, 00:00/i)).toBeVisible();
  });

  it('does not display remaining time with no end date', () => {
    // ARRANGE
    const sourceAchievement = createAchievement({
      id: 9,
      title: 'That Was Easy',
      description: 'foo',
    });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
        activeThrough: undefined,
      }),
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    expect(screen.queryByText(/ends/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/from now/i)).not.toBeInTheDocument();
  });

  it('given the user has unlocked the achievement of the week, gives the achievement badge a border', () => {
    // ARRANGE
    const sourceAchievement = createAchievement({
      id: 9,
      title: 'That Was Easy',
      description: 'foo',
    });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
        activeThrough: undefined,
      }),
      doesUserHaveUnlock: true, // !!
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    const imgEl = screen.getByRole('img', { name: /that was easy/i });
    expect(imgEl).toHaveClass('outline outline-[gold]');
  });

  it('given the user has not unlocked the achievement of the week, does not give the achievement badge a border', () => {
    // ARRANGE
    const sourceAchievement = createAchievement({
      id: 9,
      title: 'That Was Easy',
      description: 'foo',
    });
    const achievementOfTheWeek = createAchievementOfTheWeekProps({
      currentEventAchievement: createEventAchievement({
        achievement: sourceAchievement,
        sourceAchievement,
        activeThrough: undefined,
      }),
      doesUserHaveUnlock: false, // !!
    });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    const imgEl = screen.getByRole('img', { name: /that was easy/i });
    expect(imgEl).not.toHaveClass('outline outline-[gold]');
  });
});
