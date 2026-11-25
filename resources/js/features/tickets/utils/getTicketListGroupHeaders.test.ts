import { createGame, createTicketListEntry } from '@/test/factories';

import { getTicketListGroupHeaders } from './getTicketListGroupHeaders';

describe('Util: getTicketListGroupHeaders', () => {
  it('given the list is not grouped, returns no row a header', () => {
    // ARRANGE
    const entries = [createTicketListEntry(), createTicketListEntry()];

    // ACT
    const headers = getTicketListGroupHeaders(null, entries);

    // ASSERT
    expect(headers).toEqual([null, null]);
  });

  it('given the list is grouped by game, puts a header above the first row of each game', () => {
    // ARRANGE
    const zelda = createGame({ id: 1 });
    const metroid = createGame({ id: 2 });
    const entries = [
      createTicketListEntry({ game: zelda }),
      createTicketListEntry({ game: zelda }),
      createTicketListEntry({ game: metroid }),
    ];

    // ACT
    const headers = getTicketListGroupHeaders(
      { field: 'game', counts: { '1': 2, '2': 1 }, isFirstGroupContinued: false },
      entries,
    );

    // ASSERT
    expect(headers).toEqual([
      { isContinued: false, ticketCount: 2 },
      null,
      { isContinued: false, ticketCount: 1 },
    ]);
  });

  it('given an achievement group has more tickets on other pages, returns the full ticket count', () => {
    // ARRANGE
    const entries = [createTicketListEntry({ ticketableType: 'achievement', ticketableId: 8 })];

    // ACT
    const headers = getTicketListGroupHeaders(
      { field: 'ticketable', counts: { 'achievement:8': 5 }, isFirstGroupContinued: false },
      entries,
    );

    // ASSERT
    expect(headers).toEqual([{ isContinued: false, ticketCount: 5 }]);
  });

  it('given the first group started on the previous page, marks only that group as continued', () => {
    // ARRANGE
    const entries = [
      createTicketListEntry({ ticketableType: 'achievement', ticketableId: 8 }),
      createTicketListEntry({ ticketableType: 'achievement', ticketableId: 7 }),
    ];

    // ACT
    const headers = getTicketListGroupHeaders(
      {
        field: 'ticketable',
        counts: { 'achievement:8': 3, 'achievement:7': 1 },
        isFirstGroupContinued: true,
      },
      entries,
    );

    // ASSERT
    expect(headers).toEqual([
      { isContinued: true, ticketCount: 3 },
      { isContinued: false, ticketCount: 1 },
    ]);
  });

  it('given an achievement and a leaderboard share an ID, still keeps them in separate groups', () => {
    // ARRANGE
    const entries = [
      createTicketListEntry({ ticketableType: 'achievement', ticketableId: 7 }),
      createTicketListEntry({ ticketableType: 'leaderboard', ticketableId: 7 }),
    ];

    // ACT
    const headers = getTicketListGroupHeaders(
      {
        field: 'ticketable',
        counts: { 'achievement:7': 1, 'leaderboard:7': 1 },
        isFirstGroupContinued: false,
      },
      entries,
    );

    // ASSERT
    expect(headers).toEqual([
      { isContinued: false, ticketCount: 1 },
      { isContinued: false, ticketCount: 1 },
    ]);
  });
});
