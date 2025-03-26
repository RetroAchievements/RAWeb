import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

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

import { SystemGamesMainRoot } from './SystemGamesMainRoot';

vi.mock('../DataTableToolbar/RandomGameButton');

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

window.plausible = vi.fn();

describe('Component: SystemGamesMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.SystemGameListPageProps>(
      <SystemGamesMainRoot />,
      {
        pageProps: {
          system: createSystem(),
          defaultDesktopPageSize: 100,
          paginatedGameListEntries: createPaginatedData([]),
          can: { develop: false },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays accessible breadcrumbs', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        system,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /all games/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: system.name })).toBeVisible();
  });

  it('displays default columns', async () => {
    // ARRANGE
    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(await screen.findByRole('columnheader', { name: /title/i }));
    expect(screen.getByRole('columnheader', { name: /achievements/i }));
    expect(screen.getByRole('columnheader', { name: /points/i }));
    expect(screen.getByRole('columnheader', { name: /rarity/i }));
    expect(screen.getByRole('columnheader', { name: /release date/i }));
    expect(screen.getByRole('columnheader', { name: /players/i }));

    expect(screen.queryByRole('columnheader', { name: /system/i })).not.toBeInTheDocument();
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
      playersTotal: 10000,
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
    });

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([
          createGameListEntry({ game: mockGame, playerGame: null }),
        ]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('cell', { name: /sonic/i })).toBeVisible();
    expect(screen.getByRole('cell', { name: '42' })).toBeVisible();
    expect(screen.getByRole('cell', { name: '500 (1,000)' })).toBeVisible();
    expect(screen.getByRole('cell', { name: '×2.00' })).toBeVisible();
    expect(screen.getByRole('cell', { name: 'Aug 24, 2006' })).toBeVisible();
    expect(screen.getByRole('cell', { name: '10,000' })).toBeVisible();
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
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
    });

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([
          createGameListEntry({ game: mockGame, isInBacklog: false, playerGame: null }),
        ]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', mockGame.id], {
      userGameListType: UserGameListType.Play,
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
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
    });

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([
          createGameListEntry({ game: mockGame, isInBacklog: true }),
        ]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
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

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([
          createGameListEntry({ game: mockGame, isInBacklog: true }),
        ]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
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
    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /columns/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /points/i }));

    // ASSERT
    expect(screen.queryByRole('columnheader', { name: /points/i })).not.toBeInTheDocument();
  });

  it('given the user cannot develop achievements, they cannot enable an Open Tickets column', async () => {
    // ARRANGE
    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /columns/i }));

    // ASSERT
    expect(
      screen.queryByRole('menuitemcheckbox', { name: /open tickets/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user can develop achievements, they can enable an Open Tickets column', async () => {
    // ARRANGE
    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: true }, // !!
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /columns/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /tickets/i }));
    await userEvent.keyboard('{escape}');

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

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })]),
        can: { develop: true },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /columns/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /tickets/i }));
    await userEvent.keyboard('{escape}');

    // ASSERT
    expect(screen.getByRole('link', { name: '2' }));
  });

  it('allows the user to search for games on the list', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /search games/i }), 'dragon quest');

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.system.game.index',
        {
          'filter[system]': `${mockSystem.id}`,
          'filter[achievementsPublished]': 'has',
          'filter[title]': 'dragon quest',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'title',
          systemId: mockSystem.id,
        },
      ]);
    });
  });

  it('by default, has the achievements published filter set to "Yes"', () => {
    // ARRANGE
    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /yes/i })).toBeVisible();
  });

  it('does not display a system/console filter', () => {
    // ARRANGE
    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.queryByTestId('filter-system')).not.toBeInTheDocument();
  });

  it('given a non-default filter is set, allows the user to click a button to reset their filters', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    vi.spyOn(axios, 'get')
      .mockResolvedValueOnce({ data: createPaginatedData([]) })
      .mockResolvedValueOnce({ data: createPaginatedData([]) }); // the GET will be called twice

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-achievementsPublished'));
    await userEvent.click(screen.getByRole('option', { name: /no/i }));

    await userEvent.click(screen.getByRole('button', { name: /reset/i }));

    // ASSERT
    const defaultFilterButtonEl = screen.getByTestId('filter-achievementsPublished');
    expect(defaultFilterButtonEl).toHaveTextContent(/yes/i);

    const systemFilterButtonEl = screen.getByTestId('filter-achievementsPublished');
    expect(systemFilterButtonEl).not.toHaveTextContent(/no/i);
  });

  it('given a filter is currently applied, shows both the filtered and unfiltered game totals', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    vi.spyOn(axios, 'get').mockResolvedValueOnce({
      data: createPaginatedData([], { total: 3, unfilteredTotal: 587 }),
    });

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: createSystem(),
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-achievementsPublished'));
    await userEvent.click(screen.getByRole('option', { name: /no/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/3 of 587 games/i)).toBeVisible();
    });
  });

  it('allows the user to sort by a string column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Title'));
    await userEvent.click(screen.getByRole('menuitem', { name: /desc/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.system.game.index',
        {
          systemId: mockSystem.id,
          'filter[system]': `${mockSystem.id}`,
          'filter[achievementsPublished]': 'has',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-title',
        },
      ]);
    });
  });

  it('allows the user to sort by a numeric column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Achievements'));
    await userEvent.click(screen.getByRole('menuitem', { name: /less/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.system.game.index',
        {
          systemId: mockSystem.id,
          'filter[system]': `${mockSystem.id}`,
          'filter[achievementsPublished]': 'has',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'achievementsPublished',
        },
      ]);
    });
  });

  it('allows the user to sort by a date column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Release Date'));
    await userEvent.click(screen.getByRole('menuitem', { name: /earliest/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.system.game.index',
        {
          systemId: mockSystem.id,
          'filter[system]': `${mockSystem.id}`,
          'filter[achievementsPublished]': 'has',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'releasedAt',
        },
      ]);
    });
  });

  it('allows the user to sort by a boolean column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /columns/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /claimed/i }));
    await userEvent.keyboard('{escape}');

    await userEvent.click(screen.getByTestId('column-header-Claimed'));
    await userEvent.click(screen.getByRole('menuitem', { name: /yes first/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.system.game.index',
        {
          systemId: mockSystem.id,
          'filter[system]': `${mockSystem.id}`,
          'filter[achievementsPublished]': 'has',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-hasActiveOrInReviewClaims',
        },
      ]);
    });
  });

  it('allows the user to hide a column via the column header button', async () => {
    // ARRANGE
    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
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
    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([], { total: 300 }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    // Mobile renders a separate element.
    expect(screen.getAllByText(/300 games/i)[0]).toBeVisible();
  });

  it('given there are multiple pages, allows the user to advance to the next page', async () => {
    // ARRANGE
    window.scrollTo = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          total: 300,
          currentPage: 1,
          perPage: 100,
        }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /next page/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.system.game.index',
        {
          systemId: mockSystem.id,
          'filter[system]': `${mockSystem.id}`,
          'filter[achievementsPublished]': 'has',
          'page[number]': 2,
          'page[size]': 100,
          sort: 'title',
        },
      ]);
    });
  });

  it("given the user presses the '/' hotkey, focuses the search input", async () => {
    // ARRANGE
    const mockSystem = createSystem();

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([], { total: 300 }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.keyboard('/');

    // ASSERT
    expect(screen.getByRole('textbox', { name: /search/i })).toHaveFocus();
  });

  it('given the user is on a mobile device, renders a list rather than a table', async () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const mockSystem = createSystem();

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

    render<App.Platform.Data.SystemGameListPageProps>(<SystemGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        system: mockSystem,
        defaultDesktopPageSize: 100,
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ASSERT
    expect(await screen.findByText(/sonic the hedgehog/i)).toBeVisible();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });
});
