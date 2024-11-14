import { render, screen } from '@/test';
import {
  createAchievement,
  createGame,
  createHomePageProps,
  createStaticData,
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

  it('given there is no achievement of the week, does not crash', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek: null }),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible link to the event page', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ staticData: createStaticData({ eventAotwForumId: 100 }) }),
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /learn more about this event/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', '/viewtopic.php?t=100');
  });

  it('given there is no accessible link to the event page, does not render a link', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ staticData: createStaticData({ eventAotwForumId: null }) }),
    });

    // ASSERT
    expect(
      screen.queryByRole('link', { name: /learn more about this event/i }),
    ).not.toBeInTheDocument();
  });

  it('has a link to the achievement', () => {
    // ARRANGE
    const achievementOfTheWeek = createAchievement({ id: 9, title: 'That Was Easy' });

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
    const achievementOfTheWeek = createAchievement({ game, id: 9, title: 'That Was Easy' });

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
    const achievementOfTheWeek = createAchievement({ game, id: 9, title: 'That Was Easy' });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    expect(screen.getByText(/that was easy/i)).toBeVisible();
    expect(screen.getByText(achievementOfTheWeek.description!)).toBeVisible();
  });

  it('displays the game title and system name', () => {
    // ARRANGE
    const system = createSystem({ name: 'Sega Genesis/Mega Drive', nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });
    const achievementOfTheWeek = createAchievement({ game, id: 9, title: 'That Was Easy' });

    render<App.Http.Data.HomePageProps>(<AchievementOfTheWeek />, {
      pageProps: createHomePageProps({ achievementOfTheWeek }),
    });

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
    expect(screen.getByText('MD')).toBeVisible();
  });
});
