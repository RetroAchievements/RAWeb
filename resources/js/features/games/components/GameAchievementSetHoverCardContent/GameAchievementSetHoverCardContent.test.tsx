import { BaseHoverCard } from '@/common/components/+vendor/BaseHoverCard';
import { render, screen } from '@/test';
import { createAchievementSet, createGameAchievementSet } from '@/test/factories';

import { GameAchievementSetHoverCardContent } from './GameAchievementSetHoverCardContent';

describe('Component: GameAchievementSetHoverCardContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet();

    const { container } = render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a game achievement set with a title, displays the title', () => {
    // ARRANGE
    const title = 'Super Mario World Achievements';
    const gameAchievementSet = createGameAchievementSet({
      title,
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(title)[0]).toBeVisible();
  });

  it('given a game achievement set without a title, displays "Base Set"', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: null,
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(/base set/i)[0]).toBeVisible();
  });

  it('given a game achievement set, displays the achievement set image', () => {
    // ARRANGE
    const imageUrl = 'https://example.com/achievement-set.png';
    const title = 'Test Achievement Set';
    const gameAchievementSet = createGameAchievementSet({
      title,
      achievementSet: createAchievementSet({
        imageAssetPathUrl: imageUrl,
      }),
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    const image = screen.getAllByRole('img')[0];
    expect(image).toBeVisible();
    expect(image).toHaveAttribute('src', imageUrl);
    expect(image).toHaveAttribute('alt', title);
  });

  it('given a game achievement set without a title, uses "Base Set" as image alt text', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: null,
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    const image = screen.getAllByRole('img')[0];
    expect(image).toHaveAttribute('alt', 'Base Set');
  });

  it('given a game achievement set, displays the number of published achievements', () => {
    // ARRANGE
    const achievementsPublished = 50;
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievementsPublished,
      }),
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(/achievements:/i)[0]).toBeVisible();
    expect(screen.getAllByText('50')[0]).toBeVisible();
  });

  it('given a game achievement set, displays the total points', () => {
    // ARRANGE
    const pointsTotal = 1000;
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        pointsTotal,
      }),
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(/points:/i)[0]).toBeVisible();
    expect(screen.getAllByText('1,000')[0]).toBeVisible();
  });

  it('given a game achievement set with weighted points, displays RetroPoints with rarity', () => {
    // ARRANGE
    const pointsTotal = 100;
    const pointsWeighted = 500;
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        pointsTotal,
        pointsWeighted,
      }),
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(/retropoints:/i)[0]).toBeVisible();
    expect(screen.getAllByText(/rarity/i)[0]).toBeVisible();
  });

  it('given a game achievement set without weighted points, displays "None yet" for RetroPoints', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        pointsWeighted: 0,
      }),
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(/retropoints:/i)[0]).toBeVisible();
    expect(screen.getAllByText(/none yet/i)[0]).toBeVisible();
  });

  it('given a game achievement set with first published date, displays the formatted date', () => {
    // ARRANGE
    const firstPublishedDate = '2023-01-15T10:30:00Z';
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievementsFirstPublishedAt: firstPublishedDate,
      }),
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(/first published:/i)[0]).toBeVisible();
    expect(screen.getAllByText(/2023|jan/i)[0]).toBeVisible();
  });

  it('given a game achievement set without first published date, displays "Unknown"', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievementsFirstPublishedAt: undefined,
      }),
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    expect(screen.getAllByText(/first published:/i)[0]).toBeVisible();
    expect(screen.getAllByText(/unknown/i)[0]).toBeVisible();
  });

  it('given a game achievement set with a long title, applies small text styling', () => {
    // ARRANGE
    const longTitle =
      'This is a very long achievement set title that exceeds twenty-four characters';
    const gameAchievementSet = createGameAchievementSet({
      title: longTitle,
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    const titleElement = screen.getAllByText(longTitle)[0];
    expect(titleElement).toBeVisible();
    expect(titleElement).toHaveClass('text-sm');
  });

  it('given a game achievement set with a short title, applies large text styling', () => {
    // ARRANGE
    const shortTitle = 'Short Title';
    const gameAchievementSet = createGameAchievementSet({
      title: shortTitle,
    });

    render(
      <BaseHoverCard open={true}>
        <GameAchievementSetHoverCardContent gameAchievementSet={gameAchievementSet} />
      </BaseHoverCard>,
    );

    // ASSERT
    const titleElement = screen.getAllByText(shortTitle)[0];
    expect(titleElement).toBeVisible();
    expect(titleElement).toHaveClass('text-lg');
  });
});
