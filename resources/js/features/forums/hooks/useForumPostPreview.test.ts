import axios from 'axios';

import { act, renderHook, waitFor } from '@/test';
import { createAchievement, createGame, createTicket, createUser } from '@/test/factories';

import { persistedGamesAtom } from '../state/forum.atoms';
import { useForumPostPreview } from './useForumPostPreview';

describe('Hook: useForumPostPreview', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useForumPostPreview());

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given content with no dynamic entities, sets preview content without making an API call', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    const { result } = renderHook(() => useForumPostPreview());

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
        tickets: [],
        users: [createUser({ displayName: 'username' })],
      },
    };

    vi.spyOn(axios, 'post').mockResolvedValueOnce(mockResponse);

    const { result } = renderHook(() => useForumPostPreview());

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
        tickets: [createTicket({ id: 12345 })],
        users: [createUser({ displayName: 'username' })],
      },
    };

    vi.spyOn(axios, 'post').mockResolvedValueOnce(mockResponse);

    const initialGames = [{ id: 456, name: 'Existing Game' }];
    const { result } = renderHook(() => useForumPostPreview(), {
      jotaiAtoms: [[persistedGamesAtom, initialGames]],
    });

    // ACT
    await act(async () => {
      await result.current.initiatePreview(contentWithEntities);
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.previewContent).toEqual(contentWithEntities);
    });

    const { persistedGames, persistedAchievements, persistedTickets, persistedUsers } =
      result.current.unsafe_getPersistedValues();

    expect(persistedGames).toHaveLength(2);
    expect(persistedGames).toEqual(
      expect.arrayContaining([
        expect.objectContaining({ id: 123 }),
        expect.objectContaining({ id: 456 }),
      ]),
    );

    expect(persistedAchievements).toHaveLength(1);
    expect(persistedTickets).toHaveLength(1);
    expect(persistedUsers).toHaveLength(1);
  });
});
