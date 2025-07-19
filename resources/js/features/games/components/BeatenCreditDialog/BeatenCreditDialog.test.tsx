import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
} from '@/test/factories';

import { BeatenCreditDialog } from './BeatenCreditDialog';

describe('Component: BeatenCreditDialog', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [] }),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given only progression achievements, shows progression metric and list', () => {
    // ARRANGE
    const progressionAchievements = [
      createAchievement({ type: 'progression', title: 'First Boss' }),
      createAchievement({ type: 'progression', title: 'Second Boss' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({ achievements: progressionAchievements }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [gameAchievementSet] }),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Progression')).toBeVisible();
    expect(screen.getByText(/0\/2/)).toBeVisible();
    expect(screen.getByText(/first boss/i)).toBeVisible();
    expect(screen.getByText(/second boss/i)).toBeVisible();

    expect(screen.queryByText(/win condition/i)).not.toBeInTheDocument();
  });

  it('given only win condition achievements, shows win condition metric and list', () => {
    // ARRANGE
    const winConditionAchievements = [
      createAchievement({ type: 'win_condition', title: 'Beat the Game' }),
      createAchievement({ type: 'win_condition', title: 'True Ending' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({ achievements: winConditionAchievements }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [gameAchievementSet] }),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Win Condition')).toBeVisible();
    expect(screen.getByText(/0\/1/)).toBeVisible();
    expect(screen.getByText(/beat the game/i)).toBeVisible();
    expect(screen.getByText(/true ending/i)).toBeVisible();

    expect(screen.queryByText(/progression/i)).not.toBeInTheDocument();
  });

  it('given both achievement types, shows both metrics in a 2-column grid', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ type: 'progression' }),
      createAchievement({ type: 'win_condition' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({ achievements }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [gameAchievementSet] }),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Progression')).toBeVisible();
    expect(screen.getByText('Win Condition')).toBeVisible();
  });

  it('given unlocked progression achievements, shows the correct count', () => {
    // ARRANGE
    const progressionAchievements = [
      createAchievement({ type: 'progression', unlockedAt: '2024-01-01' }),
      createAchievement({ type: 'progression', unlockedAt: '2024-01-02' }),
      createAchievement({ type: 'progression', unlockedAt: undefined }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({ achievements: progressionAchievements }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [gameAchievementSet] }),
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/2\/3/)).toBeVisible();
  });

  it('given any unlocked win condition achievements, shows 1/1', () => {
    // ARRANGE
    const winConditionAchievements = [
      createAchievement({ type: 'win_condition', unlockedAt: '2024-01-01' }),
      createAchievement({ type: 'win_condition', unlockedAt: undefined }),
      createAchievement({ type: 'win_condition', unlockedAt: undefined }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({ achievements: winConditionAchievements }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [gameAchievementSet] }),
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/1\/1/)).toBeVisible();
  });

  it('given multiple unlocked win condition achievements, still shows 1/1', () => {
    // ARRANGE
    const winConditionAchievements = [
      createAchievement({ type: 'win_condition', unlockedAt: '2024-01-01' }),
      createAchievement({ type: 'win_condition', unlockedAt: '2024-01-02' }),
      createAchievement({ type: 'win_condition', unlockedAt: '2024-01-03' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({ achievements: winConditionAchievements }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [gameAchievementSet] }),
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/1\/1/)).toBeVisible();
  });

  it('given non-core achievement sets, ignores them', () => {
    // ARRANGE
    const bonusAchievements = [
      createAchievement({ type: 'progression', title: 'Bonus Achievement' }),
    ];

    const bonusSet = createGameAchievementSet({
      type: 'bonus',
      achievementSet: createAchievementSet({ achievements: bonusAchievements }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [bonusSet] }),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/bonus achievement/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
  });

  it('given multiple core sets, combines their achievements', () => {
    // ARRANGE
    const coreSet1 = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({
        achievements: [createAchievement({ type: 'progression', title: 'Achievement 1' })],
      }),
    });

    const coreSet2 = createGameAchievementSet({
      type: 'core',
      achievementSet: createAchievementSet({
        achievements: [createAchievement({ type: 'progression', title: 'Achievement 2' })],
      }),
    });

    render(
      <BaseDialog open={true}>
        <BeatenCreditDialog />
      </BaseDialog>,
      {
        pageProps: {
          game: createGame({ gameAchievementSets: [coreSet1, coreSet2] }),
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/achievement 1/i)).toBeVisible();
    expect(screen.getByText(/achievement 2/i)).toBeVisible();
    expect(screen.getByText(/0\/2/)).toBeVisible();
  });
});
