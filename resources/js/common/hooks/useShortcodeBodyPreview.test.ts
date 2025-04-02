import axios from 'axios';

import { persistedGamesAtom } from '@/common/state/shortcode.atoms';
import { act, renderHook, waitFor } from '@/test';
import {
  createAchievement,
  createGame,
  createGameSet,
  createRaEvent,
  createTicket,
  createUser,
} from '@/test/factories';

import { useShortcodeBodyPreview } from './useShortcodeBodyPreview';

describe('Hook: useShortcodeBodyPreview', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given content with no dynamic entities, sets preview content without making an API call', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    const { result } = renderHook(() => useShortcodeBodyPreview());

    const simpleContent = 'Hello world!';

    // ACT
    await act(async () => {
      await result.current.initiatePreview(simpleContent);
    });

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();

    await waitFor(() => {
      expect(result.current.previewContent).toEqual(simpleContent);
    });
  });

  it('given content with dynamic entities, fetches and merges new entities', async () => {
    // ARRANGE
    const contentWithEntities = '@username and [game=123]';
    const mockResponse = {
      data: {
        achievements: [],
        games: [createGame({ id: 123, title: 'Test Game' })],
        hubs: [],
        events: [],
        tickets: [],
        users: [createUser({ displayName: 'username' })],
      },
    };

    vi.spyOn(axios, 'post').mockResolvedValueOnce(mockResponse);

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithEntities);
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.previewContent).toEqual(contentWithEntities);
    });
  });

  it('given content with dynamic entities, updates persisted atoms with new data', async () => {
    // ARRANGE
    const contentWithEntities = '@username and [game=123]';
    const mockResponse = {
      data: {
        achievements: [createAchievement({ id: 9 })],
        games: [createGame({ id: 123, title: 'Test Game' })],
        hubs: [createGameSet({ id: 1, title: '[Central]' })],
        events: [
          createRaEvent({
            id: 2,
            legacyGame: createGame({ title: 'Achievement of the Week 2025' }),
          }),
        ],
        tickets: [createTicket({ id: 12345 })],
        users: [createUser({ displayName: 'username' })],
      },
    };

    vi.spyOn(axios, 'post').mockResolvedValueOnce(mockResponse);

    const initialGames = [{ id: 456, name: 'Existing Game' }];
    const { result } = renderHook(() => useShortcodeBodyPreview(), {
      jotaiAtoms: [
        [persistedGamesAtom, initialGames],
        //
      ],
    });

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithEntities);
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.previewContent).toEqual(contentWithEntities);
    });

    const {
      persistedGames,
      persistedHubs,
      persistedEvents,
      persistedAchievements,
      persistedTickets,
      persistedUsers,
    } = result.current.unsafe_getPersistedValues();

    expect(persistedGames).toHaveLength(2);
    expect(persistedGames).toEqual(
      expect.arrayContaining([
        expect.objectContaining({ id: 123 }),
        expect.objectContaining({ id: 456 }),
      ]),
    );

    expect(persistedHubs).toHaveLength(1);
    expect(persistedEvents).toHaveLength(1);
    expect(persistedAchievements).toHaveLength(1);
    expect(persistedTickets).toHaveLength(1);
    expect(persistedUsers).toHaveLength(1);
  });
});
