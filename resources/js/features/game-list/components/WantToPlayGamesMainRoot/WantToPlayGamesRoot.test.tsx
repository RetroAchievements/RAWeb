import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { UserGameListType } from '@/common/utils/generatedAppConstants';
import { render, screen, waitFor } from '@/test';
import {
  createGame,
  createGameListEntry,
  createPaginatedData,
  createSystem,
  createZiggyProps,
} from '@/test/factories';

import { WantToPlayGamesRoot } from './WantToPlayGamesRoot';

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

describe('Component: WantToPlayGamesRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.UserGameListPageProps>(
      <WantToPlayGamesRoot />,
      {
        pageProps: {
          filterableSystemOptions: [],
          paginatedGameListEntries: createPaginatedData([]),
          can: { develop: false },
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays default columns', () => {
    // ARRANGE
    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('columnheader', { name: /title/i }));
    expect(screen.getByRole('columnheader', { name: /system/i }));
    expect(screen.getByRole('columnheader', { name: /achievements/i }));
    expect(screen.getByRole('columnheader', { name: /points/i }));
    expect(screen.getByRole('columnheader', { name: /rarity/i }));
    expect(screen.getByRole('columnheader', { name: /release date/i }));
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
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
    });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([
          createGameListEntry({ game: mockGame, playerGame: null }),
        ]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('cell', { name: /sonic/i })).toBeVisible();
    expect(screen.getByRole('cell', { name: /md/i })).toBeVisible();
    expect(screen.getByRole('cell', { name: '42' })).toBeVisible();
    expect(screen.getByRole('cell', { name: '500 (1,000)' })).toBeVisible();
    expect(screen.getByRole('cell', { name: '×2.00' })).toBeVisible();
    expect(screen.getByRole('cell', { name: 'Aug 24, 2006' })).toBeVisible();
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
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
    });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /remove/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(['api.user-game-list.destroy', mockGame.id], {
      data: { userGameListType: UserGameListType.Play },
    });
  });

  it('allows users to undo removing games from their backlog', async () => {
    // ARRANGE
    window.HTMLElement.prototype.setPointerCapture = vi.fn();

    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
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
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
    });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /remove/i }));

    const undoButtonEl = await screen.findByRole('button', { name: /undo/i });
    await userEvent.click(undoButtonEl);

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(['api.user-game-list.destroy', mockGame.id], {
      data: { userGameListType: UserGameListType.Play },
    });

    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', mockGame.id], {
      userGameListType: UserGameListType.Play,
    });
  });

  it('allows users to toggle column visibility', async () => {
    // ARRANGE
    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /view/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /points/i }));

    // ASSERT
    expect(screen.queryByRole('columnheader', { name: /points/i })).not.toBeInTheDocument();
  });

  it('given the user cannot develop achievements, they cannot enable an Open Tickets column', async () => {
    // ARRANGE
    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /view/i }));

    // ASSERT
    expect(
      screen.queryByRole('menuitemcheckbox', { name: /open tickets/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user can develop achievements, they can enable an Open Tickets column', async () => {
    // ARRANGE
    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: true },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /view/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /tickets/i }));

    // ASSERT
    expect(screen.getByRole('columnheader', { name: /tickets/i })).toBeVisible();
  });

  it('given a game row has a non-zero amount of open tickets, the cell links to the tickets page', async () => {
    // ARRANGE
    const mockSystem = createSystem({
      nameShort: 'MD',
      iconUrl: 'https://retroachievements.org/test.png',
    });

    const mockGame = createGame({
      id: 1,
      title: 'Sonic the Hedgehog',
      system: mockSystem,
      achievementsPublished: 42,
      pointsTotal: 500,
      pointsWeighted: 1000,
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
      numUnresolvedTickets: 2,
    });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })]),
        can: { develop: true },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /view/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /tickets/i }));

    // ASSERT
    expect(screen.getByRole('link', { name: '2' }));
  });

  it('allows the user to search for games on the list', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /search games/i }), 'dragon quest');

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.user-game-list.index',
        {
          'filter[title]': 'dragon quest',
          'page[number]': 1,
          sort: null,
        },
      ]);
    });
  });

  it('allows the user to filter by system/console', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-System'));
    await userEvent.click(screen.getByRole('option', { name: /genesis/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.user-game-list.index',
        {
          'filter[system]': '1',
          'page[number]': 1,
          sort: null,
        },
      ]);
    });
  });

  it('given a filter is currently applied, shows both the filtered and unfiltered game totals', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    vi.spyOn(axios, 'get').mockResolvedValueOnce({
      data: createPaginatedData([], { total: 3, unfilteredTotal: 587 }),
    });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-System'));
    await userEvent.click(screen.getByRole('option', { name: /genesis/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/3 of 587 games/i)).toBeVisible();
    });
  });

  it('allows the user to filter by whether the game has achievements', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-Has achievements'));
    await userEvent.click(screen.getByRole('option', { name: /yes/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.user-game-list.index',
        {
          'filter[achievementsPublished]': 'has',
          'page[number]': 1,
          sort: null,
        },
      ]);
    });
  });

  it('allows the user to sort by a string column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-System'));
    await userEvent.click(screen.getByRole('menuitem', { name: /desc/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.user-game-list.index',
        {
          'page[number]': 1,
          sort: '-system',
        },
      ]);
    });
  });

  it('allows the user to sort by a numeric column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Achievements'));
    await userEvent.click(screen.getByRole('menuitem', { name: /less/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.user-game-list.index',
        {
          'page[number]': 1,
          sort: 'achievementsPublished',
        },
      ]);
    });
  });

  it('allows the user to sort by a date column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Release Date'));
    await userEvent.click(screen.getByRole('menuitem', { name: /earliest/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.user-game-list.index',
        {
          'page[number]': 1,
          sort: 'releasedAt',
        },
      ]);
    });
  });

  it('allows the user to hide a column via the column header button', async () => {
    // ARRANGE
    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Achievements'));
    await userEvent.click(screen.getByRole('menuitem', { name: /hide/i }));

    // ASSERT
    expect(screen.queryByTestId('column-header-Achievements')).not.toBeInTheDocument();
  });

  it('always displays the number of total games', () => {
    // ARRANGE
    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { total: 300 }),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByText(/300 games/i)).toBeVisible();
  });

  it('given there are multiple pages, allows the user to advance to the next page', async () => {
    // ARRANGE
    window.scrollTo = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          total: 300,
          currentPage: 1,
          perPage: 1,
        }),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /next page/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.user-game-list.index',
        {
          'page[number]': 2,
          sort: null,
        },
      ]);
    });
  });

  it("given the user presses the '/' hotkey, focuses the search input", async () => {
    // ARRANGE
    render<App.Community.Data.UserGameListPageProps>(<WantToPlayGamesRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.keyboard('/');

    // ASSERT
    expect(screen.getByRole('textbox', { name: /search/i })).toHaveFocus();
  });
});
