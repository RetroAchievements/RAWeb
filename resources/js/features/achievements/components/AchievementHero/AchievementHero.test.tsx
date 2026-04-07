import userEvent from '@testing-library/user-event';

import { isEditModeAtom } from '@/features/achievements/state/achievements.atoms';
import { render, screen } from '@/test';
import { createAchievement, createEventAchievement, createGame } from '@/test/factories';

import { AchievementHero } from './AchievementHero';

describe('Component: AchievementHero', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
      unlockPercentage: '25',
    });

    const { container } = render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the achievement title, description, points, and RetroPoints', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Beat the Final Boss',
      description: 'Defeat the last enemy',
      points: 25,
      pointsWeighted: 200,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText('Beat the Final Boss')).toBeVisible();
    expect(screen.getByText(/defeat the last enemy/i)).toBeVisible();
    expect(screen.getAllByText(/points/i).length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText(/retropoints/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given the user has unlocked the achievement, shows the unlocked badge', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-03-10T12:00:00Z',
      badgeUnlockedUrl: '/badge/unlocked.png',
      badgeLockedUrl: '/badge/locked.png',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    const img = screen.getByRole('img');
    expect(img).toHaveAttribute('src', '/badge/unlocked.png');
  });

  it('given the user has not unlocked the achievement, shows the locked badge', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      badgeUnlockedUrl: '/badge/unlocked.png',
      badgeLockedUrl: '/badge/locked.png',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    const img = screen.getByRole('img');
    expect(img).toHaveAttribute('src', '/badge/locked.png');
  });

  it('given the achievement has a type, displays the type indicator', () => {
    // ARRANGE
    const achievement = createAchievement({
      type: 'missable',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getAllByText(/missable/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given the achievement has no type, does not display a type indicator label', () => {
    // ARRANGE
    const achievement = createAchievement({
      type: null,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/missable/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/progression/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/win condition/i)).not.toBeInTheDocument();
  });

  it('given the user unlocked hardcore, shows the hardcore unlock label', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedHardcoreAt: '2024-06-15T08:30:00Z',
      unlockedAt: '2024-06-15T08:30:00Z',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/unlocked hardcore/i)).toBeVisible();
  });

  it('given the user unlocked softcore only, shows the softcore unlock label', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-06-15T08:30:00Z',
      unlockedHardcoreAt: undefined,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText('Unlocked')).toBeVisible();
    expect(screen.queryByText(/unlocked hardcore/i)).not.toBeInTheDocument();
  });

  it('given the user has not unlocked the achievement, does not show an unlock status', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/unlocked/i)).not.toBeInTheDocument();
  });

  it('displays the unlock rate percentage', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: true,
      unlockPercentage: '0.4567',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getAllByText(/45\.67/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given no unlock percentage, falls back to zero', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: true,
      unlockPercentage: undefined,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getAllByText(/0\.00/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given the game has zero players total, does not set a max on the progress bar', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: true,
      game: createGame({ playersTotal: 0 }),
      unlocksTotal: 0,
      unlocksHardcore: 0,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    const progressBarEl = screen.getByRole('progressbar');
    expect(progressBarEl).toHaveAttribute('aria-valuemax', '100');
  });

  it('displays softcore and hardcore unlock counts', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: true,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 500,
      unlocksHardcore: 300,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/200 softcore/i)).toBeVisible();
    expect(screen.getByText(/300 hardcore/i)).toBeVisible();
  });

  it('given an unpromoted achievement, does not display the progress bar or player counts', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: false,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 500,
      unlocksHardcore: 300,
      unlockPercentage: '0.50',
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/200 softcore/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/300 hardcore/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/unlock rate/i)).not.toBeInTheDocument();
  });

  it('given an unpromoted achievement, displays the "Not promoted" label', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: false,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 500,
      unlocksHardcore: 300,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getAllByText(/not promoted/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given a promoted achievement, does not display the "Not promoted" label', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: true,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 500,
      unlocksHardcore: 300,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/not promoted/i)).not.toBeInTheDocument();
  });

  it('given the user is in edit mode and can edit the title, the title input is editable', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Original Title',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementTitle: true },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ASSERT
    const titleInput = screen.getByRole('textbox', { name: 'Achievement title' });
    expect(titleInput).not.toHaveAttribute('readOnly');
  });

  it('given the user is not in edit mode, renders the title as plain text instead of an input', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Original Title',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementTitle: true },
      },
    });

    // ASSERT
    expect(screen.queryByRole('textbox', { name: 'Achievement title' })).not.toBeInTheDocument();
    expect(screen.getByText('Original Title')).toBeVisible();
  });

  it('given the user is not in edit mode, renders the description as plain text instead of a textarea', () => {
    // ARRANGE
    const achievement = createAchievement({
      description: 'Original description',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementDescription: true },
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('textbox', { name: 'Achievement description' }),
    ).not.toBeInTheDocument();
    expect(screen.getByText('Original description')).toBeVisible();
  });

  it('given the user is in edit mode and can edit the type, shows the type select', () => {
    // ARRANGE
    const achievement = createAchievement({
      type: 'missable',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementType: true },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ASSERT
    expect(
      screen.getAllByRole('combobox', { name: 'Achievement type' }).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('given the user is in edit mode and can edit points, shows the points select', () => {
    // ARRANGE
    const achievement = createAchievement({
      points: 25,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementPoints: true },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ASSERT
    expect(
      screen.getAllByRole('combobox', { name: 'Achievement points' }).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('given the achievement is a subset and the user can edit the type, does not show progression or win condition options', async () => {
    // ARRANGE
    const achievement = createAchievement({
      type: null,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementType: true },
        gameAchievementSet: { type: 'bonus' },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getAllByRole('combobox', { name: 'Achievement type' })[0]);

    // ASSERT
    expect(screen.getByRole('option', { name: 'None' })).toBeVisible();
    expect(screen.getByRole('option', { name: 'Missable' })).toBeVisible();
    expect(screen.queryByRole('option', { name: 'Progression' })).not.toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Win Condition' })).not.toBeInTheDocument();
  });

  it('given the achievement is not a subset and the user can edit the type, shows all type options', async () => {
    // ARRANGE
    const achievement = createAchievement({
      type: null,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementType: true },
        gameAchievementSet: { type: 'core' },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getAllByRole('combobox', { name: 'Achievement type' })[0]);

    // ASSERT
    expect(screen.getByRole('option', { name: 'None' })).toBeVisible();
    expect(screen.getByRole('option', { name: 'Missable' })).toBeVisible();
    expect(screen.getByRole('option', { name: 'Progression' })).toBeVisible();
    expect(screen.getByRole('option', { name: 'Win Condition' })).toBeVisible();
  });

  it('given the user is in edit mode and can edit the description, the description textarea is editable', () => {
    // ARRANGE
    const achievement = createAchievement({
      description: 'Original description',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementDescription: true },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ASSERT
    const descriptionTextarea = screen.getByRole('textbox', { name: 'Achievement description' });
    expect(descriptionTextarea).not.toHaveAttribute('readOnly');
  });

  it('given a promoted achievement in edit mode, applies disabled styling to the progress bar section', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: true,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 500,
      unlocksHardcore: 300,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ASSERT
    const progressBarEl = screen.getByRole('progressbar');
    expect(progressBarEl.closest('.pointer-events-none')).toBeTruthy();
  });

  it('given the user types into the description, strips newline characters', async () => {
    // ARRANGE
    const achievement = createAchievement({
      description: 'Hello',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementDescription: true },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ACT
    const textarea = screen.getByRole('textbox', { name: 'Achievement description' });
    await userEvent.clear(textarea);
    await userEvent.type(textarea, 'Line one\nLine two');

    // ASSERT
    expect(textarea).toHaveValue('Line oneLine two');
  });

  it('given the user presses Enter in the description, prevents the default behavior', async () => {
    // ARRANGE
    const achievement = createAchievement({
      description: 'Hello',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: {
        achievement,
        can: { updateAchievementDescription: true },
      },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ACT
    const textarea = screen.getByRole('textbox', { name: 'Achievement description' });
    await userEvent.type(textarea, '{Enter}');

    // ASSERT
    expect(textarea).toHaveValue('Hello');
  });

  it('given the user is in edit mode, hides the "Not promoted" label', () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: false,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 500,
      unlocksHardcore: 300,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
      jotaiAtoms: [
        [isEditModeAtom, true],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByText(/not promoted/i)).not.toBeInTheDocument();
  });

  it('given the achievement has zero points, does not display points or RetroPoints', () => {
    // ARRANGE
    const achievement = createAchievement({
      points: 0,
      pointsWeighted: 0,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/points/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/retropoints/i)).not.toBeInTheDocument();
  });

  it('given a revealed event achievement, links the title to the source achievement', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Event Version',
      game: createGame({ playersTotal: 500 }),
      unlocksTotal: 100,
      unlocksHardcore: 100,
    });

    const sourceAchievement = createAchievement({ id: 12345 });
    const eventAchievement = createEventAchievement({
      sourceAchievement,
      isObfuscated: false,
    });

    render(<AchievementHero />, {
      pageProps: { achievement, eventAchievement, isEventGame: true },
    });

    // ASSERT
    const titleLink = screen.getByRole('link', { name: 'Event Version' });
    expect(titleLink).toBeVisible();
    expect(titleLink).toHaveAttribute('href', expect.stringContaining('achievement.show'));
  });

  it('given an obfuscated event achievement, does not link the title', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Hidden Event',
      game: createGame({ playersTotal: 500 }),
      unlocksTotal: 100,
      unlocksHardcore: 100,
    });

    const eventAchievement = createEventAchievement({
      sourceAchievement: createAchievement(),
      isObfuscated: true,
    });

    render(<AchievementHero />, {
      pageProps: { achievement, eventAchievement, isEventGame: true },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: 'Hidden Event' })).not.toBeInTheDocument();
    expect(screen.getByText('Hidden Event')).toBeVisible();
  });

  it('given a non-event achievement, does not link the title', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Regular Achievement',
      game: createGame({ playersTotal: 500 }),
      unlocksTotal: 100,
      unlocksHardcore: 100,
    });

    render(<AchievementHero />, {
      pageProps: { achievement, isEventGame: false },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: 'Regular Achievement' })).not.toBeInTheDocument();
    expect(screen.getByText('Regular Achievement')).toBeVisible();
  });

  it('given the achievement is for an event game, shows "unlocks" instead of "softcore" and "hardcore"', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 500 }),
      isPromoted: true,
      unlocksTotal: 200,
      unlocksHardcore: 200,
      unlockPercentage: '40',
    });

    render(<AchievementHero />, {
      pageProps: { achievement, isEventGame: true },
    });

    // ASSERT
    expect(screen.getByText(/200 unlocks/i)).toBeVisible();
    expect(screen.queryByText(/softcore/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/hardcore/i)).not.toBeInTheDocument();
  });
});
