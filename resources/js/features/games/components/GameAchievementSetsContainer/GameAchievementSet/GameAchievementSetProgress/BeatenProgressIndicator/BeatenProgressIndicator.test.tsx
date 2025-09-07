import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createAchievement, createPlayerGame } from '@/test/factories';

import { BeatenProgressIndicator } from './BeatenProgressIndicator';

// Mock the BeatenCreditDialog since we're not testing its internals.
vi.mock('@/features/games/components/BeatenCreditDialog', () => ({
  BeatenCreditDialog: () => <div>Mocked BeatenCreditDialog</div>,
}));

describe('Component: BeatenProgressIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<BeatenProgressIndicator achievements={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the player has not beaten the game, renders the indicator button with low opacity', () => {
    // ARRANGE
    const playerGame = createPlayerGame({
      beatenAt: null,
      beatenHardcoreAt: null,
    });

    render(<BeatenProgressIndicator achievements={[]} />, {
      pageProps: { playerGame },
    });

    // ASSERT
    const button = screen.getByRole('button', { name: /beaten/i });
    expect(button).toHaveClass('text-opacity-30');
  });

  it('given the player has beaten the game in softcore, renders the indicator button with full opacity', () => {
    // ARRANGE
    const playerGame = createPlayerGame({
      beatenAt: '2024-01-01T00:00:00Z',
      beatenHardcoreAt: null,
    });

    render(<BeatenProgressIndicator achievements={[]} />, {
      pageProps: { playerGame },
    });

    // ASSERT
    const button = screen.getByRole('button', { name: /beaten/i });
    expect(button).toHaveClass('text-opacity-100');
  });

  it('given the player has beaten the game in hardcore, renders the indicator button with full opacity', () => {
    // ARRANGE
    const playerGame = createPlayerGame({
      beatenAt: null,
      beatenHardcoreAt: '2024-01-01T00:00:00Z',
    });

    render(<BeatenProgressIndicator achievements={[]} />, {
      pageProps: { playerGame },
    });

    // ASSERT
    const button = screen.getByRole('button', { name: /beaten/i });
    expect(button).toHaveClass('text-opacity-100');
  });

  it('given only progression achievements exist and none are unlocked, shows correct counts', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText('0/3')[0]).toBeVisible();
    });
    expect(screen.getAllByText('Progression')[0]).toBeVisible();
  });

  it('given progression achievements exist with some unlocked, shows correct progress', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ type: 'progression', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ type: 'progression', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const elements = await screen.findAllByText('2/4');
    expect(elements[0]).toBeVisible();

    expect(screen.getAllByText('Progression')[0]).toBeVisible();
  });

  it('given only a win condition achievement exists and it is not unlocked, shows correct counts', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({
        type: 'win_condition',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const elements = await screen.findAllByText('0/1');
    expect(elements[0]).toBeVisible();

    expect(screen.getAllByText('Win Condition')[0]).toBeVisible();
  });

  it('given a win condition achievement is unlocked, shows 1/1 for win condition', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ type: 'win_condition', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const elements = await screen.findAllByText('1/1');
    expect(elements[0]).toBeVisible();

    expect(screen.getAllByText('Win Condition')[0]).toBeVisible();
  });

  it('given both progression and win condition achievements exist, shows both progress indicators', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ type: 'progression', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({
        type: 'win_condition',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const totalElements = await screen.findAllByText('1/3');
    expect(totalElements[0]).toBeVisible();

    expect(screen.getAllByText('1/2')[0]).toBeVisible();
    expect(screen.getAllByText('Progression')[0]).toBeVisible();
    expect(screen.getAllByText('0/1')[0]).toBeVisible();
    expect(screen.getAllByText('Win Condition')[0]).toBeVisible();
  });

  it('given the player has more softcore unlocks, shows "Beaten Progress (Softcore)" in the tooltip', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({
        type: 'progression',
        unlockedAt: '2024-01-01T00:00:00Z',
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({
        type: 'progression',
        unlockedAt: '2024-01-01T00:00:00Z',
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({ type: 'progression', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const elements = await screen.findAllByText('Beaten Progress (Softcore)');
    expect(elements[0]).toBeVisible();
  });

  it('given the player has more hardcore unlocks, shows "Beaten Progress" in the tooltip', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ type: 'progression', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ type: 'progression', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({
        type: 'progression',
        unlockedAt: '2024-01-01T00:00:00Z',
        unlockedHardcoreAt: undefined,
      }),
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const elements = await screen.findAllByText('Beaten Progress');
    expect(elements[0]).toBeVisible();
  });

  it('given no beaten-related achievements are unlocked and the user has more softcore points than hardcore, shows the softcore label', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
    ];

    const user = createAuthenticatedUser({
      points: 1000,
      pointsSoftcore: 2000, // !!
    });

    render(<BeatenProgressIndicator achievements={achievements} />, {
      pageProps: { auth: { user } },
    });

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const elements = await screen.findAllByText('Beaten Progress (Softcore)');
    expect(elements[0]).toBeVisible();
  });

  it('given no beaten-related achievements are unlocked and the user has more hardcore points than softcore, does not show the softcore label', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({
        type: 'progression',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
    ];

    const user = createAuthenticatedUser({
      points: 2000,
      pointsSoftcore: 1000,
    });

    render(<BeatenProgressIndicator achievements={achievements} />, {
      pageProps: { auth: { user } },
    });

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const elements = await screen.findAllByText('Beaten Progress');
    expect(elements[0]).toBeVisible();

    expect(screen.queryByText(/softcore/i)).not.toBeInTheDocument();
  });

  it('given achievements with mixed types including non-beaten types, only counts beaten-related achievements', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ type: 'progression', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ type: 'win_condition', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ type: null }), // Regular achievement.
      createAchievement({ type: 'missable' }), // Another type.
    ];

    render(<BeatenProgressIndicator achievements={achievements} />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    const totalElements = await screen.findAllByText('2/2');
    expect(totalElements[0]).toBeVisible();
  });

  it('given the indicator button is clicked, opens the beaten credit dialog', async () => {
    // ARRANGE
    render(<BeatenProgressIndicator achievements={[]} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /beaten/i }));

    // ASSERT
    expect(screen.getByText('Mocked BeatenCreditDialog')).toBeVisible();
  });
});
