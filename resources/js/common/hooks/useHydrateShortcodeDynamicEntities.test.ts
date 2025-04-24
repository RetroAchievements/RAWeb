import * as JotaiUtilsModule from 'jotai/utils';

import { renderHook } from '@/test';

import {
  persistedAchievementsAtom,
  persistedGamesAtom,
  persistedHubsAtom,
  persistedTicketsAtom,
  persistedUsersAtom,
} from '../state/shortcode.atoms';
import { useHydrateShortcodeDynamicEntities } from './useHydrateShortcodeDynamicEntities';

vi.mock('jotai/utils', async () => {
  const actual = await vi.importActual('jotai/utils');

  return {
    ...actual,
    useHydrateAtoms: vi.fn(),
  };
});

describe('Hook: useHydrateShortcodeDynamicEntities', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const initialDynamicEntities = {
      achievements: [],
      games: [],
      hubs: [],
      events: [],
      tickets: [],
      users: [],
    };

    // ACT
    const { result } = renderHook(
      () => useHydrateShortcodeDynamicEntities(initialDynamicEntities),
      {
        pageProps: {
          dynamicEntities: {
            achievements: [],
            games: [],
            hubs: [],
            events: [],
            tickets: [],
            users: [],
          },
        },
      },
    );

    // ASSERT
    expect(result).toBeTruthy();
  });

  it('given dynamic entities, calls useHydrateAtoms with the correct atoms and values', () => {
    // ARRANGE
    const mockUseHydrateAtoms = vi.spyOn(JotaiUtilsModule, 'useHydrateAtoms');

    const dynamicEntities = {
      achievements: [{ id: 1 }],
      games: [{ id: 2 }],
      hubs: [{ id: 3 }],
      tickets: [{ id: 4 }],
      users: [{ id: 5 }],
    };

    // ACT
    renderHook(() => useHydrateShortcodeDynamicEntities(dynamicEntities as any), {
      pageProps: {
        dynamicEntities: {
          achievements: [],
          games: [],
          hubs: [],
          events: [],
          tickets: [],
          users: [],
        },
      },
    });

    // ASSERT
    expect(mockUseHydrateAtoms).toHaveBeenCalledWith([
      [persistedAchievementsAtom, dynamicEntities.achievements],
      [persistedGamesAtom, dynamicEntities.games],
      [persistedHubsAtom, dynamicEntities.hubs],
      [persistedTicketsAtom, dynamicEntities.tickets],
      [persistedUsersAtom, dynamicEntities.users],
    ]);
  });
});
