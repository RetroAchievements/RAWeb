import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createSystem,
} from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { AchievementBreadcrumbs } from './AchievementBreadcrumbs';

describe('Component: AchievementBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <AchievementBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Games list', () => {
    // ARRANGE
    render(<AchievementBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />);

    // ASSERT
    const allGamesLinkEl = screen.getByRole('link', { name: /all games/i });
    expect(allGamesLinkEl).toBeVisible();
    expect(allGamesLinkEl).toHaveAttribute('href', 'game.index');
  });

  it('given a system, has a link to the system games list', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(
      <AchievementBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const systemGamesLinkEl = screen.getByRole('link', { name: /nintendo 64/i });
    expect(systemGamesLinkEl).toBeVisible();
    expect(systemGamesLinkEl).toHaveAttribute('href', `system.game.index,${system.id}`);
  });

  it('given a game, has a link to the game page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(
      <AchievementBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: game.title });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', expect.stringContaining('game.show'));
  });

  it('given an achievement, has a link to the achievement page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });
    const achievement = createAchievement();

    render(
      <AchievementBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        achievement={achievement}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const achievementLinkEl = screen.getByRole('link', { name: achievement.title });
    expect(achievementLinkEl).toBeVisible();
    expect(achievementLinkEl).toHaveAttribute('href', expect.stringContaining('achievement.show'));
  });

  it('given a gameAchievementSet, renders the subset title as a link between game and current page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Genesis/Mega Drive' });
    const game = createGame({ title: 'Sonic the Hedgehog', system });
    const gameAchievementSet = createGameAchievementSet({
      title: 'Perfect Bonus123',
      achievementSet: createAchievementSet({ id: 456 }),
    });

    render(
      <AchievementBreadcrumbs
        t_currentPageLabel={'Perfect Green Hill 1' as TranslatedString}
        game={game}
        system={system}
        gameAchievementSet={gameAchievementSet}
      />,
    );

    // ASSERT
    const subsetLinkEl = screen.getByRole('link', { name: /perfect bonus123/i });
    expect(subsetLinkEl).toBeVisible();
    expect(subsetLinkEl).toHaveAttribute('href', expect.stringContaining('game.show'));
  });

  it('given a gameAchievementSet with no title, does not render a subset breadcrumb', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });
    const game = createGame({ title: 'Some Game', system });
    const gameAchievementSet = createGameAchievementSet({
      title: null,
      achievementSet: createAchievementSet({ id: 789 }),
    });

    render(
      <AchievementBreadcrumbs
        t_currentPageLabel={'Some Achievement' as TranslatedString}
        game={game}
        system={system}
        gameAchievementSet={gameAchievementSet}
      />,
    );

    // ASSERT
    const allLinks = screen.getAllByRole('link');
    const linkTexts = allLinks.map((el) => el.textContent);
    expect(linkTexts).not.toContain(expect.stringContaining('null'));
  });

  it('stylizes tags that are within game titles', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: '~Hack~ Super Junkoid' });
    const achievement = createAchievement({ title: 'Some Achievement' });

    render(
      <AchievementBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        achievement={achievement}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    expect(screen.queryByText('~')).not.toBeInTheDocument();

    const hackEl = screen.getByText(/hack/i);
    expect(hackEl).toBeVisible();
    expect(hackEl.nodeName).toEqual('SPAN');
  });
});
