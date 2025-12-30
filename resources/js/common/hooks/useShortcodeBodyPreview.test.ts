import axios from 'axios';

import { persistedGamesAtom } from '@/common/state/shortcode.atoms';
import { act, renderHook, waitFor } from '@/test';
import {
  createAchievement,
  createGame,
  createGameSet,
  createRaEvent,
  createTriggerTicket,
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

  it('given content with no dynamic entities, makes an API call with body text', async () => {
    // ARRANGE
    const simpleContent = 'Hello world!';

    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        achievements: [],
        games: [],
        hubs: [],
        events: [],
        tickets: [],
        users: [],
        convertedBody: simpleContent,
      },
    });

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(simpleContent);
    });

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(['api.shortcode-body.preview'], { body: simpleContent });

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
        convertedBody: '[user=username] and [game=123]',
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
      expect(result.current.previewContent).toEqual('[user=username] and [game=123]'); // !! uses converted body
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
        tickets: [createTriggerTicket({ id: 12345 })],
        users: [createUser({ displayName: 'username' })],
        convertedBody: '[user=username] and [game=123]',
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
      expect(result.current.previewContent).toEqual('[user=username] and [game=123]');
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

  it('given content with a game set shortcode, converts to backing game ID in the preview', async () => {
    // ARRANGE
    const contentWithSetId = 'Check out [game=668?set=8659]';

    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        achievements: [],
        games: [createGame({ id: 29895, title: 'Sonic the Hedgehog [Subset - Perfect Bonus]' })],
        hubs: [],
        events: [],
        tickets: [],
        users: [],
        convertedBody: 'Check out [game=29895]',
      },
    });

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithSetId);
    });

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(['api.shortcode-body.preview'], {
        body: contentWithSetId,
      });
    });

    await waitFor(() => {
      expect(result.current.previewContent).toEqual('Check out [game=29895]');
    });

    const { persistedGames } = result.current.unsafe_getPersistedValues();
    expect(persistedGames).toHaveLength(1);
    expect(persistedGames).toEqual(
      expect.arrayContaining([expect.objectContaining({ id: 29895 })]),
    );
  });

  it('given content with multiple game set shortcodes, converts all to backing game IDs', async () => {
    // ARRANGE
    const contentWithSetIds = 'Try [game=668?set=8659] and [game=1?set=9534]';

    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        achievements: [],
        games: [
          createGame({ id: 29895, title: 'Sonic the Hedgehog [Subset - Perfect Bonus]' }),
          createGame({ id: 28000, title: 'Another Subset Game' }),
        ],
        hubs: [],
        events: [],
        tickets: [],
        users: [],
        convertedBody: 'Try [game=29895] and [game=28000]',
      },
    });

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithSetIds);
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.previewContent).toEqual('Try [game=29895] and [game=28000]');
    });
  });

  it('given content with mixed game shortcodes (with and without sets), handles both correctly', async () => {
    // ARRANGE
    const contentWithMixedShortcodes = 'Play [game=668] or [game=668?set=8659]';

    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        achievements: [],
        games: [
          createGame({ id: 668, title: 'Sonic the Hedgehog' }),
          createGame({ id: 29895, title: 'Sonic the Hedgehog [Subset - Perfect Bonus]' }),
        ],
        hubs: [],
        events: [],
        tickets: [],
        users: [],
        convertedBody: 'Play [game=668] or [game=29895]',
      },
    });

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithMixedShortcodes);
    });

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(['api.shortcode-body.preview'], {
        body: contentWithMixedShortcodes,
      });
    });

    await waitFor(() => {
      expect(result.current.previewContent).toEqual('Play [game=668] or [game=29895]');
    });
  });

  it('given content with a game set shortcode but no backing game found, falls back to the original shortcode', async () => {
    // ARRANGE
    const contentWithInvalidSetId = 'Check out [game=668?set=99999]';

    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        achievements: [],
        games: [],
        hubs: [],
        events: [],
        tickets: [],
        users: [],
        convertedBody: 'Check out [game=668?set=99999]',
      },
    });

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithInvalidSetId);
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.previewContent).toEqual('Check out [game=668?set=99999]');
    });
  });

  it('given content with multiple game set shortcodes but only some have backing games, converts stuff correctly', async () => {
    // ARRANGE
    const contentWithMixedValidSets = 'Try [game=1?set=9534] and [game=2?set=8888]';

    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        achievements: [],
        games: [createGame({ id: 29895, title: 'Sonic Subset' })],
        hubs: [],
        events: [],
        tickets: [],
        users: [],
        convertedBody: 'Try [game=29895] and [game=2?set=8888]',
      },
    });

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithMixedValidSets);
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.previewContent).toEqual('Try [game=29895] and [game=2?set=8888]'); // !!
    });
  });

  it('given content with both regular games and set shortcodes but the backend returns no backing games for sets, keeps set shortcodes unchanged', async () => {
    // ARRANGE
    const contentWithMixed = 'Play [game=668] or [game=1?set=9534]';

    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        achievements: [],
        games: [
          createGame({ id: 668, title: 'Sonic the Hedgehog' }),
          // ... no backing game for set ID 9534 ...
        ],
        hubs: [],
        events: [],
        tickets: [],
        users: [],
        convertedBody: 'Play [game=668] or [game=1?set=9534]',
      },
    });

    const { result } = renderHook(() => useShortcodeBodyPreview());

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithMixed);
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.previewContent).toEqual('Play [game=668] or [game=1?set=9534]');
    });
  });
});
