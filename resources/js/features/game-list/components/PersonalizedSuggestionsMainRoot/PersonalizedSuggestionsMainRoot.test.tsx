import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createGame,
  createGameSuggestionEntry,
  createPaginatedData,
  createSystem,
} from '@/test/factories';

import { PersonalizedSuggestionsMainRoot } from './PersonalizedSuggestionsMainRoot';

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

describe('Component: PersonalizedSuggestionsMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameSuggestPageProps>(
      <PersonalizedSuggestionsMainRoot />,
      {
        pageProps: {
          paginatedGameListEntries: createPaginatedData([]),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays default columns', async () => {
    // ARRANGE
    render<App.Platform.Data.GameSuggestPageProps>(<PersonalizedSuggestionsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedGameListEntries: createPaginatedData([
          createGameSuggestionEntry(),
          createGameSuggestionEntry(),
        ]),
      },
    });

    // ASSERT
    expect(await screen.findByRole('columnheader', { name: /title/i }));
    expect(screen.getByRole('columnheader', { name: /system/i }));
    expect(screen.getByRole('columnheader', { name: /achievements/i }));
    expect(screen.getByRole('columnheader', { name: /points/i }));
    expect(screen.getByRole('columnheader', { name: /reasoning/i }));
    expect(screen.getByRole('columnheader', { name: /progress/i })).toBeVisible();
  });

  it('shows game rows', () => {
    // ARRANGE
    const mockSystem = createSystem({
      nameShort: 'MD',
      iconUrl: 'https://retroachievements.org/test.png',
    });

    const mockGame = createGame({
      title: 'Sonic the Hedgehog',
      system: mockSystem,
      achievementsPublished: 42,
      pointsTotal: 500,
      pointsWeighted: 1000,
    });

    render<App.Platform.Data.GameSuggestPageProps>(<PersonalizedSuggestionsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedGameListEntries: createPaginatedData([
          createGameSuggestionEntry({
            game: mockGame,
            playerGame: null,
            suggestionReason: 'random',
          }),
        ]),
      },
    });

    // ASSERT
    expect(screen.getByRole('cell', { name: /sonic/i })).toBeVisible();
    expect(screen.getByRole('cell', { name: /md/i })).toBeVisible();
    expect(screen.getByRole('cell', { name: '42' })).toBeVisible();
    expect(screen.getByRole('cell', { name: '500 (1,000)' })).toBeVisible();
    expect(screen.getByRole('cell', { name: /randomly selected/i })).toBeVisible();
  });

  it('allows users to add games to their backlog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const mockSystem = createSystem({
      nameShort: 'MD',
      iconUrl: 'https://retroachievements.org/test.png',
    });

    const mockGame = createGame({
      title: 'Sonic the Hedgehog',
      system: mockSystem,
      achievementsPublished: 42,
      pointsTotal: 500,
      pointsWeighted: 1000,
    });

    render<App.Platform.Data.GameSuggestPageProps>(<PersonalizedSuggestionsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedGameListEntries: createPaginatedData([
          createGameSuggestionEntry({
            game: mockGame,
            playerGame: null,
            suggestionReason: 'random',
            isInBacklog: false, // !!
          }),
        ]),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', mockGame.id], {
      userGameListType: 'play',
    });
  });

  it('allows users to remove games from their backlog', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const mockSystem = createSystem({
      nameShort: 'MD',
      iconUrl: 'https://retroachievements.org/test.png',
    });

    const mockGame = createGame({
      title: 'Sonic the Hedgehog',
      system: mockSystem,
      achievementsPublished: 42,
      pointsTotal: 500,
      pointsWeighted: 1000,
    });

    render<App.Platform.Data.GameSuggestPageProps>(<PersonalizedSuggestionsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedGameListEntries: createPaginatedData([
          createGameSuggestionEntry({
            game: mockGame,
            playerGame: null,
            suggestionReason: 'random',
            isInBacklog: true, // !!
          }),
        ]),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /remove/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(['api.user-game-list.destroy', mockGame.id], {
      data: { userGameListType: 'play' },
    });
  });

  it('does not allow users to toggle column visibility', () => {
    // ARRANGE
    render<App.Platform.Data.GameSuggestPageProps>(<PersonalizedSuggestionsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedGameListEntries: createPaginatedData([
          createGameSuggestionEntry(),
          createGameSuggestionEntry(),
        ]),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /columns/i })).not.toBeInTheDocument();
  });

  it('does not allow the user to sort by any column', () => {
    // ARRANGE
    render<App.Platform.Data.GameSuggestPageProps>(<PersonalizedSuggestionsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedGameListEntries: createPaginatedData([
          createGameSuggestionEntry(),
          createGameSuggestionEntry(),
        ]),
      },
    });

    // ASSERT
    expect(screen.queryByTestId('column-header-System')).not.toBeInTheDocument();
  });

  it('allows the user to load another set of random suggestions', async () => {
    // ARRANGE
    const reloadSpy = vi.spyOn(router, 'reload').mockResolvedValueOnce({} as any);

    render<App.Platform.Data.GameSuggestPageProps>(<PersonalizedSuggestionsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedGameListEntries: createPaginatedData([
          createGameSuggestionEntry(),
          createGameSuggestionEntry(),
        ]),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /roll again/i }));

    // ASSERT
    expect(reloadSpy).toHaveBeenCalledOnce();
    expect(reloadSpy).toHaveBeenCalledWith({ only: ['paginatedGameListEntries'] });
  });
});
