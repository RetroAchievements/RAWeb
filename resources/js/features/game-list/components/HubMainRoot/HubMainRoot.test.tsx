import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createGame,
  createGameListEntry,
  createGameSet,
  createPaginatedData,
  createSystem,
  createZiggyProps,
} from '@/test/factories';

import { HubMainRoot } from './HubMainRoot';

vi.mock('../DataTableToolbar/RandomGameButton');

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

window.plausible = vi.fn();

describe('Component: HubMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        filterableSystemOptions: [],
        breadcrumbs: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        hub: createGameSet(),
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays default columns', async () => {
    // ARRANGE
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        breadcrumbs: [],
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          unfilteredTotal: 1,
        }),
        can: { develop: false },
        hub: createGameSet(),
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(await screen.findByRole('columnheader', { name: /title/i }));
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

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData(
          [createGameListEntry({ game: mockGame, playerGame: null })],
          { unfilteredTotal: 1 },
        ),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
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

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData(
          [createGameListEntry({ game: mockGame, isInBacklog: false })],
          { unfilteredTotal: 1 },
        ),
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

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData(
          [createGameListEntry({ game: mockGame, isInBacklog: true })],
          { unfilteredTotal: 1 },
        ),
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

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData(
          [createGameListEntry({ game: mockGame, isInBacklog: true })],
          { unfilteredTotal: 1 },
        ),
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
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          unfilteredTotal: 1,
        }),
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
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          unfilteredTotal: 1,
        }),
        can: { develop: false }, // !!
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
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          unfilteredTotal: 1,
        }),
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

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })], {
          unfilteredTotal: 1,
        }),
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

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          unfilteredTotal: 1,
        }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /search games/i }), 'dragon quest');

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'either',
          'filter[title]': 'dragon quest',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'title',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it('by default, has the achievements published filter set to "Both"', () => {
    // ARRANGE
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          unfilteredTotal: 1,
        }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /both/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /yes/i })).not.toBeInTheDocument();
  });

  it('allows the user to filter by system/console', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [
          createSystem({ id: 1, name: 'Genesis/Mega Drive', nameShort: 'MD' }),
          createSystem({ id: 2, name: 'NES/Famicom', nameShort: 'NES' }),
        ],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          unfilteredTotal: 1,
        }),
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
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'either',
          'filter[system]': '1',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'title',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it('given a non-default filter is set, allows the user to click a button to reset their filters', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    vi.spyOn(axios, 'get')
      .mockResolvedValueOnce({ data: createPaginatedData([], { unfilteredTotal: 1 }) })
      .mockResolvedValueOnce({ data: createPaginatedData([], { unfilteredTotal: 1 }) }); // the GET will be called twice

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [
          createSystem({ id: 1, name: 'Genesis/Mega Drive', nameShort: 'MD' }),
          createSystem({ id: 2, name: 'NES/Famicom', nameShort: 'NES' }),
        ],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-system'));
    await userEvent.click(screen.getByRole('option', { name: /genesis/i }));

    await userEvent.click(screen.getByRole('button', { name: /reset/i }));

    // ASSERT
    const defaultFilterButtonEl = screen.getByTestId('filter-achievementsPublished');
    expect(defaultFilterButtonEl).toHaveTextContent(/both/i);

    const systemFilterButtonEl = screen.getByTestId('filter-system');
    expect(systemFilterButtonEl).not.toHaveTextContent(/md/i);
  });

  it('given a filter is currently applied, shows both the filtered and unfiltered game totals', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    vi.spyOn(axios, 'get').mockResolvedValueOnce({
      data: createPaginatedData([], { total: 3, unfilteredTotal: 587 }),
    });

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [
          createSystem({ id: 1, name: 'Genesis/Mega Drive' }),
          createSystem({ id: 2, name: 'NES/Famicom', nameShort: 'NES' }),
        ],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
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

  it('allows the user to change the "has achievements" filter, with only a single option being set', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();

    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByTestId('filter-achievementsPublished'));
    await userEvent.click(screen.getByRole('option', { name: /no/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledWith([
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'none',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'title',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it('allows the user to sort by a string column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
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
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'either',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-system',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it('allows the user to sort by a numeric column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
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
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'either',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'achievementsPublished',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it('allows the user to sort by a date column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
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
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'either',
          'page[number]': 1,
          'page[size]': 25,
          sort: 'releasedAt',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it('allows the user to sort by a boolean column', async () => {
    // ARRANGE
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
        can: { develop: false },
        ziggy: createZiggyProps(),
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
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'either',
          'page[number]': 1,
          'page[size]': 25,
          sort: '-hasActiveOrInReviewClaims',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it('allows the user to hide a column via the column header button', async () => {
    // ARRANGE
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
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
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 300, total: 300 }),
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

    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub,
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([createGameListEntry()], {
          total: 300,
          unfilteredTotal: 300,
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
        'api.hub.game.index',
        {
          'filter[achievementsPublished]': 'either',
          'page[number]': 2,
          'page[size]': 50,
          sort: 'title',
          gameSet: hub.id,
        },
      ]);
    });
  });

  it("given the user presses the '/' hotkey, focuses the search input", async () => {
    // ARRANGE
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([], { unfilteredTotal: 1 }),
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
    });

    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        hub: createGameSet(),
        breadcrumbs: [],
        auth: { user: createAuthenticatedUser() },
        filterableSystemOptions: [createSystem({ id: 1, name: 'Genesis/Mega Drive' })],
        paginatedGameListEntries: createPaginatedData([createGameListEntry({ game: mockGame })], {
          unfilteredTotal: 1,
        }),
        can: { develop: false },
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ASSERT
    expect(screen.queryByRole('table')).not.toBeInTheDocument();

    expect(await screen.findByText(/sonic the hedgehog/i)).toBeVisible();
  });

  it('given the hub has a content warning, displays the content warning dialog', () => {
    // ARRANGE
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        filterableSystemOptions: [],
        breadcrumbs: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        hub: createGameSet({ hasMatureContent: true }),
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('alertdialog', { name: /content warning/i })).toBeVisible();
  });

  it('given the hub does not have a content warning, does not display the content warning dialog', () => {
    // ARRANGE
    render<App.Platform.Data.HubPageProps>(<HubMainRoot />, {
      pageProps: {
        filterableSystemOptions: [],
        breadcrumbs: [],
        paginatedGameListEntries: createPaginatedData([]),
        can: { develop: false },
        hub: createGameSet({ hasMatureContent: false }),
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.queryByRole('alertdialog', { name: /content warning/i })).not.toBeInTheDocument();
  });
});
