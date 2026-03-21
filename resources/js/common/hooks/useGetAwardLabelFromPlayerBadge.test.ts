import { renderHook } from '@/test';
import { createPlayerBadge } from '@/test/factories/createPlayerBadge';

import { useGetAwardLabelFromPlayerBadge } from './useGetAwardLabelFromPlayerBadge';

describe('Hook: useGetAwardLabelFromPlayerBadge', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useGetAwardLabelFromPlayerBadge());

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given awardType is mastery and awardTier is truthy, returns Mastered', () => {
    // ARRANGE
    const { result } = renderHook(() => useGetAwardLabelFromPlayerBadge());
    const playerBadge = createPlayerBadge({ awardType: 'mastery', awardTier: 1 });

    // ACT
    const label = result.current.getAwardLabelFromPlayerBadge(playerBadge);

    // ASSERT
    expect(label).toEqual('Mastered');
  });

  it('given awardType is mastery and awardTier is falsy, returns Completed', () => {
    // ARRANGE
    const { result } = renderHook(() => useGetAwardLabelFromPlayerBadge());
    const playerBadge = createPlayerBadge({ awardType: 'mastery', awardTier: 0 });

    // ACT
    const label = result.current.getAwardLabelFromPlayerBadge(playerBadge);

    // ASSERT
    expect(label).toEqual('Completed');
  });

  it('given awardType is game_beaten and awardTier is truthy, returns Beaten', () => {
    // ARRANGE
    const { result } = renderHook(() => useGetAwardLabelFromPlayerBadge());
    const playerBadge = createPlayerBadge({ awardType: 'game_beaten', awardTier: 1 });

    // ACT
    const label = result.current.getAwardLabelFromPlayerBadge(playerBadge);

    // ASSERT
    expect(label).toEqual('Beaten');
  });

  it('given awardType is game_beaten and awardTier is falsy, returns Beaten (softcore)', () => {
    // ARRANGE
    const { result } = renderHook(() => useGetAwardLabelFromPlayerBadge());
    const playerBadge = createPlayerBadge({ awardType: 'game_beaten', awardTier: 0 });

    // ACT
    const label = result.current.getAwardLabelFromPlayerBadge(playerBadge);

    // ASSERT
    expect(label).toEqual('Beaten (softcore)');
  });

  it('given awardType is neither mastery nor game_beaten, returns Finished', () => {
    // ARRANGE
    const { result } = renderHook(() => useGetAwardLabelFromPlayerBadge());
    const playerBadge = createPlayerBadge({ awardType: 'mastery' });
    (playerBadge as unknown as { awardType: string }).awardType = 'other_type';

    // ACT
    const label = result.current.getAwardLabelFromPlayerBadge(playerBadge);

    // ASSERT
    expect(label).toEqual('Finished');
  });
});
