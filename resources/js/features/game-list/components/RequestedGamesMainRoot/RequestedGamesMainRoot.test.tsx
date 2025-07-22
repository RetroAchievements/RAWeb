import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createGame,
  createGameListEntry,
  createPaginatedData,
  createSetUserRequestInfo,
  createSystem,
  createUser,
  createZiggyProps,
} from '@/test/factories';

import { RequestedGamesMainRoot } from './RequestedGamesMainRoot';

vi.mock('../DataTableToolbar/RandomGameButton');

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

window.HTMLElement.prototype.scrollIntoView = vi.fn();
window.plausible = vi.fn();

describe('Component: RequestedGamesMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no target user, displays the "Most Requested Sets" heading', () => {
    // ARRANGE
    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /most requested sets/i })).toBeVisible();
  });

  it('given a target user, displays the user breadcrumbs and heading', () => {
    // ARRANGE
    const targetUser = createUser({ displayName: 'TestUser' });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        targetUser,
        userRequestInfo: createSetUserRequestInfo(),
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /set requests/i })).toBeVisible();
    expect(screen.getByText(targetUser.displayName)).toBeVisible();
  });

  it('given a target user with request info, displays some of their request statistics', () => {
    // ARRANGE
    const targetUser = createUser({ displayName: 'TestUser' });
    const userRequestInfo = createSetUserRequestInfo({
      used: 8,
      total: 10,
      pointsForNext: 500,
    });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        targetUser,
        userRequestInfo,
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(/8 of 10 requests made/i)).toBeVisible();
  });

  it('given the user is viewing their own requests, displays points until next request', () => {
    // ARRANGE
    const authenticatedUser = createAuthenticatedUser({ displayName: 'TestUser' });
    const userRequestInfo = createSetUserRequestInfo({
      used: 8,
      total: 10,
      pointsForNext: 500,
    });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: authenticatedUser },
        targetUser: authenticatedUser,
        userRequestInfo,
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(/500 points until you earn another request/i)).toBeVisible();
  });

  it('displays default columns', async () => {
    // ARRANGE
    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(await screen.findByRole('columnheader', { name: /title/i }));
    expect(screen.getByRole('columnheader', { name: /system/i }));
    expect(screen.getByRole('columnheader', { name: /release date/i }));
    expect(screen.getByRole('columnheader', { name: /requests/i }));
    expect(screen.getByRole('columnheader', { name: /claimed/i }));
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
      numRequests: 15,
    });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([
          createGameListEntry({ game: mockGame, playerGame: null }),
        ]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('cell', { name: /sonic/i })).toBeVisible();
    expect(screen.getByRole('cell', { name: /md/i })).toBeVisible();
    expect(screen.getByRole('cell', { name: 'Aug 24, 2006' })).toBeVisible();
    expect(screen.getByRole('link', { name: '15' })).toBeVisible(); // !! requests count as link
    expect(screen.getByRole('cell', { name: /no/i })).toBeVisible(); // !! accessible claimed status
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

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
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
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
    });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
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
      data: { userGameListType: 'play' },
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

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
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
      data: { userGameListType: 'play' },
    });

    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', mockGame.id], {
      userGameListType: 'play',
    });
  });

  it('allows users to toggle column visibility', async () => {
    // ARRANGE
    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /columns/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /claimed/i }));

    // ASSERT
    expect(screen.queryByRole('columnheader', { name: /claimed/i })).not.toBeInTheDocument();
  });

  it('allows the user to search for games on the list', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
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
        'api.set-request.index',
        {
          'filter[system]': 'supported',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'any',
          'filter[title]': 'dragon quest',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-numRequests',
        },
      ]);
    });
  });

  it('given a target user, uses the user-specific API route', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });
    const targetUser = createUser({ displayName: 'TestUser' });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        targetUser,
        userRequestInfo: createSetUserRequestInfo(),
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /search games/i }), 'sonic');

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.set-request.user',
        {
          user: targetUser.displayName,
          'filter[system]': 'supported',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'any',
          'filter[user]': targetUser.displayName,
          'filter[title]': 'sonic',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'title',
        },
      ]);
    });
  });

  it('by default, has the claimed filter set to "Any"', () => {
    // ARRANGE
    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByTestId('filter-hasActiveOrInReviewClaims')).toHaveTextContent(/any/i);
  });

  it('allows the user to filter by system/console', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [
          createSystem({ id: 1, name: 'Genesis/Mega Drive', nameShort: 'MD' }),
          createSystem({ id: 2, name: 'NES/Famicom', nameShort: 'NES' }),
        ],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-system'));
    await userEvent.click(screen.getByRole('option', { name: /genesis/i }));

    // ASSERT
    const systemFilterButtonEl = screen.getByTestId('filter-system');
    expect(systemFilterButtonEl).toHaveTextContent(/md/i);

    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.set-request.index',
        {
          'filter[system]': '1',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'any',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-numRequests',
        },
      ]);
    });
  });

  it('given a non-default filter is set, allows the user to click a button to reset their filters', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    vi.spyOn(axios, 'get')
      .mockResolvedValueOnce({ data: createPaginatedData([]) })
      .mockResolvedValueOnce({ data: createPaginatedData([]) }); // the GET will be called twice

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [
          createSystem({ id: 1, name: 'Genesis/Mega Drive', nameShort: 'MD' }),
          createSystem({ id: 2, name: 'NES/Famicom', nameShort: 'NES' }),
        ],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-system'));
    await userEvent.click(screen.getByRole('option', { name: /genesis/i }));

    await userEvent.click(screen.getByRole('button', { name: /reset/i }));

    // ASSERT
    const systemFilterButtonEl = screen.getByTestId('filter-system');
    expect(systemFilterButtonEl).not.toHaveTextContent(/md/i);
  });

  it('given a filter is currently applied, shows both the filtered and unfiltered game totals', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    vi.spyOn(axios, 'get').mockResolvedValueOnce({
      data: createPaginatedData([], { total: 3, unfilteredTotal: 587 }),
    });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [
          createSystem({ id: 1, name: 'Genesis/Mega Drive' }),
          createSystem({ id: 2, name: 'NES/Famicom', nameShort: 'NES' }),
        ],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-system'));
    await userEvent.click(screen.getByRole('option', { name: /genesis/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/3 of 587 games/i)).toBeVisible();
    });
  });

  it('allows the user to filter by claimed status', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-hasActiveOrInReviewClaims'));
    await userEvent.click(screen.getByRole('option', { name: /unclaimed/i })); // !! only unclaimed games

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.set-request.index',
        {
          'filter[system]': 'supported',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'unclaimed',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-numRequests',
        },
      ]);
    });
  });

  it('allows the user to sort by a string column', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-System'));
    await userEvent.click(screen.getByRole('menuitem', { name: /desc/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.set-request.index',
        {
          'filter[system]': 'supported',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'any',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-system',
        },
      ]);
    });
  });

  it('allows the user to sort by the Release Date column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
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
        'api.set-request.index',
        {
          'filter[system]': 'supported',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'any',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'releasedAt',
        },
      ]);
    });
  });

  it('allows the user to sort by the Requests column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Requests'));
    await userEvent.click(screen.getByRole('menuitem', { name: /less/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.set-request.index',
        {
          'filter[system]': 'supported',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'any',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'numRequests',
        },
      ]);
    });
  });

  it('allows the user to hide a column via the column header button', async () => {
    // ARRANGE
    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('column-header-Requests'));
    await userEvent.click(screen.getByRole('menuitem', { name: /hide/i }));

    // ASSERT
    expect(screen.queryByTestId('column-header-Requests')).not.toBeInTheDocument();
  });

  it('always displays the number of total games', () => {
    // ARRANGE
    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
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

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          total: 300,
          currentPage: 1,
          perPage: 50,
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
        'api.set-request.index',
        {
          'filter[system]': 'supported',
          'filter[achievementsPublished]': 'none',
          'filter[hasActiveOrInReviewClaims]': 'any',
          'page[number]': 2,
          'page[size]': 50,
          sort: '-numRequests',
        },
      ]);
    });
  });

  it("given the user presses the '/' hotkey, focuses the search input", async () => {
    // ARRANGE
    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([]),
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

    const mockGame = createGame({
      id: 1,
      title: 'Sonic the Hedgehog',
      system: createSystem({ id: 1 }),
      achievementsPublished: 42,
      pointsTotal: 500,
      pointsWeighted: 1000,
      releasedAt: '2006-08-24T00:56:00+00:00',
      releasedAtGranularity: 'day',
      numUnresolvedTickets: 2,
      numRequests: 10,
    });

    render<App.Platform.Data.GameListPageProps>(<RequestedGamesMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })]),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ASSERT
    expect(await screen.findByRole('list')).toBeVisible();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });
});
