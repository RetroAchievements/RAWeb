import axios from 'axios';

import { act, renderHook, waitFor } from '@/test';

import { useResetGameProgressForm } from './useResetGameProgressForm';

// Suppress "Error: AggregateError" noise from mocking axios.
console.error = vi.fn();

vi.mock('./useResettableGamesQuery', () => ({
  useResettableGamesQuery: vi.fn().mockReturnValue({ data: [] }),
}));

vi.mock('./useResettableGameAchievementsQuery', () => ({
  useResettableGameAchievementsQuery: vi.fn().mockReturnValue({
    data: [
      { id: 10, title: 'Achievement 1' },
      { id: 20, title: 'Achievement 2' },
    ],
  }),
}));

describe('Hook: useResetGameProgressForm', () => {
  beforeEach(() => {
    vi.spyOn(window, 'confirm').mockReturnValue(true);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useResetGameProgressForm());

    // ASSERT
    expect(result.current).toBeDefined();
  });

  it('given the mutation is called with an achievementId, sends it to the achievement destroy route', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useResetGameProgressForm());

    // ACT
    await act(async () => {
      await result.current.mutation.mutateAsync({ achievementId: '10' });
    });

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(['api.user.achievement.destroy', '10']);
  });

  it('given a single achievement is reset and others remain, only adds the achievement to the already-reset list', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useResetGameProgressForm());

    // ACT
    await act(async () => {
      await result.current.mutation.mutateAsync({ achievementId: '10' });
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.alreadyResetAchievementIds).toContain(10);
    });
  });

  it('given the last remaining achievement is reset, also marks the game as reset', async () => {
    // ARRANGE
    const { useResettableGameAchievementsQuery } =
      await import('./useResettableGameAchievementsQuery');
    vi.mocked(useResettableGameAchievementsQuery).mockReturnValue({
      data: [{ id: 10, title: 'Only Achievement' }],
    } as any);

    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useResetGameProgressForm());

    // ACT
    await act(async () => {
      await result.current.mutation.mutateAsync({ gameId: '5', achievementId: '10' });
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.alreadyResetAchievementIds).toContain(10);
      expect(result.current.alreadyResetGameIds).toContain(5);
    });
  });
});
